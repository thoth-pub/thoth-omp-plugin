<?php

/** CLI-only preconditions and independent API/persistence checks for Thoth scenarios. */

use APP\facades\Repo;
use APP\submission\Submission;
use APP\plugins\generic\thoth\classes\encryption\DataEncryption;
use Illuminate\Support\Facades\DB;
use PKP\cliTool\CommandLineTool;
use PKP\db\DAORegistry;

if (PHP_SAPI !== 'cli' || getenv('THOTH_DISPOSABLE') !== '1') {
    exit(1);
}
define('INDEX_FILE_LOCATION', getcwd() . '/index.php');
require './lib/pkp/classes/cliTool/CommandLineTool.php';
require __DIR__ . '/../../tests/environment/cypress-client.php';

try {
    $credentials = thothCypressCredentials();
    $tool = new CommandLineTool();
    $context = Services::get('context')->get(1);
    if ($context->getPath() !== 'publicknowledge') {
        throw new RuntimeException('Expected Public Knowledge Press dataset');
    }
    $command = $argv[1] ?? '';
    if ($command === 'configure') {
        $settings = DAORegistry::getDAO('PluginSettingsDAO');
        // Scheduled tasks and their e-mails are outside these integration scenarios.
        $settings->updateSetting(0, 'acronplugin', 'enabled', false, 'bool');
        $settings->updateSetting(1, 'thothplugin', 'enabled', true, 'bool');
        $settings->updateSetting(1, 'thothplugin', 'token', (new DataEncryption())->encryptString($credentials['token']), 'string');
        echo "Disposable plugin configured\n";
    } elseif (in_array($command, ['create', 'create-complete', 'create-draft', 'create-linked-draft'], true)) {
        $published = in_array($command, ['create', 'create-complete'], true);
        $status = $published ? Submission::STATUS_PUBLISHED : Submission::STATUS_QUEUED;
        $key = bin2hex(random_bytes(16));
        $group = $argv[2] ?? null;
        if ($group !== null && !preg_match('/^[a-zA-Z0-9-]{1,64}$/D', $group)) {
            throw new RuntimeException('Invalid fixture group');
        }
        // Fixed fictional books keep failures reproducible; only the test suffix varies.
        $books = [
            'create' => [
                'title' => 'Open Science and Scholarly Publishing',
                'abstract' => 'An introduction to open research practices and the role of university presses '
                    . 'in making scholarly knowledge accessible to wider audiences.',
            ],
            'create-draft' => [
                'title' => 'Libraries and the Preservation of Digital Memory',
                'abstract' => 'A study of how academic libraries preserve digital collections and support '
                    . 'long-term access to the cultural and scientific record.',
            ],
            'create-linked-draft' => [
                'title' => 'Open Access and University Presses',
                'abstract' => 'An examination of editorial practices, sustainable publishing models and '
                    . 'the changing relationship between university presses and their readers.',
            ],
        ];
        $book = $books[$command === 'create-complete' ? 'create' : $command];
        $suffix = ' [' . ($group !== null ? $group . '-' : '') . substr($key, 0, 10) . ']';
        $title = $book['title'] . $suffix;
        $submission = Repo::submission()->newDataObject([
            'contextId' => 1, 'locale' => 'en', 'status' => $status,
            'stageId' => WORKFLOW_STAGE_ID_PRODUCTION, 'submissionProgress' => '',
            'workType' => Submission::WORK_TYPE_AUTHORED_WORK,
        ]);
        $publication = Repo::publication()->newDataObject([
            'locale' => 'en', 'title' => ['en' => $title], 'abstract' => ['en' => $book['abstract']],
            'urlPath' => 'thoth-cypress-' . $key,
            'status' => $status, 'datePublished' => $published ? '2020-01-01' : null,
        ]);
        $id = Repo::submission()->add($submission, $publication, $context);
        $publicationId = Repo::submission()->get($id)->getData('currentPublicationId');
        $fixture = ['publicationId' => $publicationId, 'key' => $key, 'submissionId' => $id, 'title' => $title,
            'imprintId' => $credentials['imprintId']];
        if ($command === 'create-complete') {
            require __DIR__ . '/CompleteBookFixture.php';
            $fixture += seedCompleteMetadata($fixture, $credentials);
        }
        if ($command === 'create-linked-draft') {
            // Seed an existing remote work with stale metadata; synchronization is tested through the UI.
            $oldTitle = 'Scholarly Communication in Transition' . $suffix;
            $work = thothFixtureGraphql(
                $credentials,
                'mutation($data: NewWork!) { createWork(data: $data) { workId } }',
                ['data' => ['workType' => 'MONOGRAPH', 'workStatus' => 'FORTHCOMING',
                    'imprintId' => $credentials['imprintId'], 'edition' => 1]]
            )['createWork'];
            thothFixtureGraphql(
                $credentials,
                'mutation($data: NewTitle!) { createTitle(data: $data, markupFormat: JATS_XML) { titleId } }',
                ['data' => ['workId' => $work['workId'], 'localeCode' => 'EN',
                    'title' => $oldTitle, 'fullTitle' => $oldTitle, 'canonical' => true]]
            );
            DB::table('submission_settings')->insert([
                'submission_id' => $id, 'locale' => '', 'setting_name' => 'thothWorkId',
                'setting_value' => $work['workId'],
            ]);
            $fixture['workId'] = $work['workId'];
            $fixture['oldTitle'] = $oldTitle;
        }
        if (!is_dir('/tmp/thoth-cypress-fixtures')) {
            mkdir('/tmp/thoth-cypress-fixtures', 0700);
        }
        file_put_contents('/tmp/thoth-cypress-fixtures/' . $key . '.json', json_encode($fixture));
        echo json_encode($fixture, JSON_THROW_ON_ERROR);
    } elseif (in_array($command, ['verify', 'inspect'], true)) {
        $key = $argv[2] ?? '';
        if (!preg_match('/^[a-f0-9]{32}$/D', $key)) {
            throw new RuntimeException('Invalid fixture key');
        }
        $fixture = json_decode(file_get_contents('/tmp/thoth-cypress-fixtures/' . $key . '.json'), true);
        // CLI has no request context: read persistence independently of plugin schema hooks.
        $workId = DB::table('submission_settings')
            ->where('submission_id', $fixture['submissionId'])
            ->where('setting_name', 'thothWorkId')->value('setting_value');
        if ($command === 'inspect') {
            echo json_encode(['workId' => $workId, 'status' =>
                DB::table('submissions')->where('submission_id', $fixture['submissionId'])->value('status')]);
            exit;
        }
        if (!$workId) {
            throw new RuntimeException('Registration did not persist its Thoth link');
        }
        $selection = isset($fixture['metadata'])
            ? file_get_contents(__DIR__ . '/completeWork.graphql')
            : 'query($id: Uuid!) { work(workId: $id) { workId workStatus workType '
                . 'imprint { imprintId } titles { title } } }';
        $work = thothFixtureGraphql(
            $credentials,
            $selection,
            ['id' => $workId]
        )['work'];
        echo json_encode(['workId' => $work['workId'], 'workStatus' => $work['workStatus'],
            'workType' => $work['workType'], 'title' => $work['titles'][0]['title'],
            'imprintId' => $work['imprint']['imprintId']] + $work, JSON_THROW_ON_ERROR);
    } else {
        throw new RuntimeException('Unknown fixture operation');
    }
} catch (Throwable $error) {
    // Do not print exceptions from HTTP clients, which may contain credentials.
    fwrite(STDERR, 'Cypress fixture failed (' . get_class($error) . ') at '
        . basename($error->getFile()) . ':' . $error->getLine() . ": check disposable setup\n");
    exit(1);
}

/** Query the real disposable API without exposing tokens or response errors. */
function thothFixtureGraphql(array $credentials, string $query, array $variables): array
{
    $request = curl_init('http://api:8000/graphql');
    curl_setopt_array($request, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 15, CURLOPT_HTTPHEADER => [
            'Content-Type: application/json', 'Authorization: Bearer ' . $credentials['token'],
        ], CURLOPT_POSTFIELDS => json_encode(['query' => $query, 'variables' => $variables])]);
    $response = json_decode(curl_exec($request), true);
    if (curl_getinfo($request, CURLINFO_RESPONSE_CODE) !== 200 || !empty($response['errors'])
        || !is_array($response['data'] ?? null)) {
        throw new RuntimeException('Thoth fixture query failed');
    }
    return $response['data'];
}
