<?php

/** Read-only preflight through OMP configuration and the plugin's real Thoth client. */

use APP\plugins\generic\thoth\classes\container\ThothContainer;
use PKP\cliTool\CommandLineTool;
use ThothApi\GraphQL\Client;

if (PHP_SAPI !== 'cli' || getenv('THOTH_DISPOSABLE') !== '1') {
    exit(1);
}
define('INDEX_FILE_LOCATION', getcwd() . '/index.php');
require './lib/pkp/classes/cliTool/CommandLineTool.php';
require __DIR__ . '/cypress-client.php';

try {
    $tool = new CommandLineTool();
    $credentials = thothCypressCredentials();
    if (app()->get('context')->get(1)?->getPath() !== 'publicknowledge') {
        throw new RuntimeException('Expected Public Knowledge Press dataset');
    }
    $container = ThothContainer::getInstance(1);
    $container->singleton('client', fn ($container) =>
        (new Client(['base_uri' => 'http://api:8000', 'allow_redirects' => false, 'timeout' => 10]))
            ->setToken($container->get('config')['token']));
    // Query the client directly: the repository caches this response for 24 hours.
    $me = $container->get('client')->me([
        'isSuperuser',
        'publisherContexts' => [
            'publisher' => ['publisherId', 'imprints' => ['imprintId']],
            'permissions' => ['workLifecycle'],
        ],
    ]);
    if ($me->getIsSuperuser() || count($me->getPublisherContexts()) !== 1) {
        throw new RuntimeException('Expected scoped test account');
    }
    foreach ($me->getPublisherContexts() as $publisherContext) {
        $publisher = $publisherContext->getPublisher();
        if ($publisher->getPublisherId() !== $credentials['publisherId']
            || !$publisherContext->getPermissions()->getWorkLifecycle()) {
            throw new RuntimeException('Unexpected publisher or permissions');
        }
        foreach ($publisher->getImprints() as $imprint) {
            if ($imprint->getImprintId() === $credentials['imprintId']) {
                echo "PASS: OMP authenticated with the disposable Thoth publisher\n";
                exit(0);
            }
        }
    }
    throw new RuntimeException('Missing fixture imprint');
} catch (Throwable $error) {
    // HTTP exceptions may contain credentials; never print their messages.
    fwrite(STDERR, "OMP/Thoth preflight failed: run prepare --dataset PATH --apply\n");
    exit(1);
}
