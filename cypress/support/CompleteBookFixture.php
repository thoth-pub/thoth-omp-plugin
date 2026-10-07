<?php

/** Metadata preconditions for the registration journey, never a replacement for registration. */

use APP\facades\Repo;
use Illuminate\Support\Facades\DB;
use PKP\db\DAORegistry;

function seedCompleteMetadata(array $fixture, array $credentials): array
{
    $publicationId = $fixture['publicationId'];
    $key = $fixture['key'];
    $doi = '10.5555/cypress-' . $key;
    $chapterDoi = $doi . '.chapter';
    $remoteUrl = 'https://example.org/books/' . $key . '.pdf';
    $reportUrl = 'https://example.org/books/' . $key . '/accessibility';
    $coverName = 'thoth-cypress-' . $key . '.png';
    // A tiny local cover exercises coverUrl metadata without requiring Thoth's optional S3 hosting.
    $publicFiles = new \APP\file\PublicFileManager();
    $publicFiles->writeContextFile(1, $coverName, base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aX1sAAAAASUVORK5CYII='
    ));
    $publication = Repo::publication()->get($publicationId);
    $publication->setData('prefix', ['en' => 'The', 'pt_BR' => 'A']);
    $publication->setData('title', ['en' => $fixture['title'], 'pt_BR' => 'Ciência aberta ' . $key]);
    $publication->setData('subtitle', ['en' => 'Practices and perspectives', 'pt_BR' => 'Práticas e perspectivas']);
    $publication->setData('abstract', [
        'en' => '<p>A study of <strong>open science</strong>.</p>',
        'pt_BR' => '<p>Um estudo sobre <em>ciência aberta</em>.</p>',
    ]);
    $publication->setData('licenseUrl', 'https://creativecommons.org/licenses/by/4.0/');
    $publication->setData('copyrightHolder', ['en' => 'Cypress Authors']);
    $publication->setData('copyrightYear', 2020);
    $publication->setData('coverImage', ['en' => [
        'uploadName' => $coverName, 'name' => 'cover.png', 'dateUploaded' => '2020-01-01',
        'altText' => 'Open science cover',
    ]]);
    $publication->setData('doiId', Repo::doi()->add(Repo::doi()->newDataObject([
        'doi' => $doi, 'contextId' => 1,
    ])));
    Repo::publication()->dao->update($publication);
    // Plugin schema hooks depend on HTTP context. Seed only these plugin-owned settings directly.
    foreach (['place' => 'Manaus', 'pageCount' => 240, 'imageCount' => 12] as $name => $value) {
        DB::table('publication_settings')->updateOrInsert(
            ['publication_id' => $publicationId, 'locale' => '', 'setting_name' => $name],
            ['setting_value' => $value]
        );
    }
    DAORegistry::getDAO('SubmissionKeywordDAO')->insertKeywords([
        'en' => ['open science', 'university presses'],
    ], $publicationId);
    // Before OMP 3.5 subjects are strings, including explicit classification prefixes.
    DAORegistry::getDAO('SubmissionSubjectDAO')->insertSubjects([
        'en' => ['BISAC:EDU000000', 'THEMA:JN', 'BIC:JN', 'LCC:Z665', 'open research'],
    ], $publicationId);

    // Author and Translator share a role ID; require the named group in this press.
    $authorGroups = DB::table('user_groups as groups')
        ->join('user_group_settings as settings', 'settings.user_group_id', '=', 'groups.user_group_id')
        ->where('groups.context_id', 1)->where('settings.setting_name', 'name')
        ->where('settings.locale', 'en')->where('settings.setting_value', 'Author')
        ->pluck('groups.user_group_id');
    if (count($authorGroups) !== 1) {
        throw new RuntimeException('Expected exactly one Author group in the fixture press');
    }
    $authorGroupId = $authorGroups[0];
    $orcidDigits = '0000' . str_pad((string) random_int(0, 99999999999), 11, '0', STR_PAD_LEFT);
    $total = 0;
    foreach (str_split($orcidDigits) as $digit) {
        $total = ($total + (int) $digit) * 2;
    }
    $checkDigit = (12 - $total % 11) % 11;
    $orcid = 'https://orcid.org/' . implode('-', str_split($orcidDigits . ($checkDigit === 10 ? 'X' : $checkDigit), 4));
    $authorId = Repo::author()->add(Repo::author()->newDataObject([
        'publicationId' => $publicationId, 'userGroupId' => $authorGroupId,
        'locale' => 'en', 'givenName' => ['en' => 'Cypress'], 'familyName' => ['en' => 'Author ' . $key],
        'email' => 'author-' . $key . '@example.org', 'includeInBrowse' => true,
        'url' => 'https://example.org/authors/' . $key,
        'orcid' => $orcid,
        'biography' => ['en' => '<p>Researcher in <strong>publishing</strong>.</p>',
            'pt_BR' => '<p>Pesquisa em <em>editoração</em>.</p>'],
    ]));
    $publication = Repo::publication()->get($publicationId);
    $publication->setData('primaryContactId', $authorId);
    Repo::publication()->dao->update($publication);

    $formatDao = DAORegistry::getDAO('PublicationFormatDAO');
    // Keep the registered English-language ISBN group; random country/group codes are not valid.
    $isbnPrefix = '9780' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
    $sum = 0;
    foreach (str_split($isbnPrefix) as $index => $digit) {
        $sum += (int) $digit * ($index % 2 ? 3 : 1);
    }
    $isbn = $isbnPrefix . ((10 - $sum % 10) % 10);
    foreach (['PDF' => 'DA', 'EPUB' => 'DA', 'Paperback' => 'BC'] as $name => $entryKey) {
        $format = $formatDao->newDataObject();
        $format->setAllData([
            'publicationId' => $publicationId, 'name' => ['en' => $name], 'entryKey' => $entryKey,
            'physicalFormat' => $entryKey === 'BC', 'isApproved' => true, 'isAvailable' => true,
            'sequence' => $name === 'PDF' ? 0 : ($name === 'EPUB' ? 1 : 2),
            'urlRemote' => $entryKey === 'DA' ? str_replace('.pdf', '.' . strtolower($name), $remoteUrl) : null,
        ]);
        $formatId = $formatDao->insertObject($format);
        if ($name === 'PDF') {
            $codeDao = DAORegistry::getDAO('IdentificationCodeDAO');
            $code = $codeDao->newDataObject();
            $code->setPublicationFormatId($formatId);
            $code->setCode('15');
            $code->setValue($isbn);
            $codeDao->insertObject($code);
        }
        // Thoth accepts either conformance standards or an exception, never both on one format.
        $accessibility = $name === 'PDF'
            ? ['accessibilityStandard' => 'WCAG21AA', 'accessibilityAdditionalStandard' => 'PDF_UA1',
                'accessibilityReportUrl' => $reportUrl]
            : ($name === 'EPUB' ? ['accessibilityException' => 'MICRO_ENTERPRISES'] : []);
        foreach ($accessibility as $field => $value) {
            DB::table('publication_format_settings')->insert([
                'publication_format_id' => $formatId, 'locale' => '',
                'setting_name' => $field, 'setting_value' => $value, 'setting_type' => 'string',
            ]);
        }
    }
    $citationDao = DAORegistry::getDAO('CitationDAO');
    foreach (['Example, A. (2019). Open research. https://doi.org/10.5555/example-reference',
        'Example, B. (2018). University presses.'] as $index => $text) {
        $citation = new \PKP\citation\Citation();
        $citation->setData('publicationId', $publicationId);
        $citation->setRawCitation($text);
        $citation->setSequence($index + 1);
        $citationDao->insertObject($citation);
    }
    $chapterDao = DAORegistry::getDAO('ChapterDAO');
    $chapter = $chapterDao->newDataObject();
    $chapter->setAllData([
        'publicationId' => $publicationId, 'sequence' => 0, 'pages' => '1-24', 'datePublished' => '2020-01-01',
        'title' => ['en' => 'Opening knowledge', 'pt_BR' => 'Abrindo o conhecimento'],
        'subtitle' => ['en' => 'An introduction', 'pt_BR' => 'Uma introdução'],
        'abstract' => ['en' => '<p>Chapter overview.</p>', 'pt_BR' => '<p>Visão geral do capítulo.</p>'],
        'doiId' => Repo::doi()->add(Repo::doi()->newDataObject(['doi' => $chapterDoi, 'contextId' => 1])),
    ]);
    $chapterId = $chapterDao->insertChapter($chapter);
    Repo::author()->dao->insertChapterAuthor($authorId, $chapterId, true, 0);

    return ['metadata' => ['doi' => 'https://doi.org/' . $doi, 'chapterDoi' => 'https://doi.org/' . $chapterDoi,
        'isbn' => $isbn, 'remoteUrl' => $remoteUrl, 'reportUrl' => $reportUrl,
        'coverName' => $coverName, 'authorFamilyName' => 'Author ' . $key, 'orcid' => $orcid]];
}
