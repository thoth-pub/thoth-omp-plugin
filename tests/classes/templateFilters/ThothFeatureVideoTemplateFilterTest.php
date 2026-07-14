<?php

/**
 * @file plugins/generic/thoth/tests/classes/templateFilters/ThothFeatureVideoTemplateFilterTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 */

namespace APP\plugins\generic\thoth\tests\classes\templateFilters;

require_once __DIR__ . '/../../../vendor/autoload.php';

use APP\plugins\generic\thoth\classes\templateFilters\ThothFeatureVideoTemplateFilter;
use PKP\tests\PKPTestCase;

class ThothFeatureVideoTemplateFilterTest extends PKPTestCase
{
    public function testAddsFeatureVideoToBookMainEntry(): void
    {
        $filter = new FeatureVideoTemplateFilterStub([
            'title' => 'Book & trailer',
            'url' => 'https://cdn.thoth.pub/trailer.mp4?x=1&y=2',
            'width' => 640,
            'height' => 360,
        ]);
        $templateManager = new FeatureVideoTemplateManagerStub();
        $output = '<div class="main_entry"><p>Abstract</p></div><!-- .main_entry -->';

        $filter->registerFilter($templateManager, 'frontend/pages/book.tpl');
        $result = $templateManager->applyOutputFilter($output);

        $this->assertStringContainsString('class="item thoth_feature_video"', $result);
        $this->assertStringContainsString('Book &amp; trailer', $result);
        $this->assertStringContainsString('src="https://cdn.thoth.pub/trailer.mp4?x=1&amp;y=2"', $result);
        $this->assertStringContainsString('width="640" height="360"', $result);
        $this->assertLessThan(
            strpos($result, '</div><!-- .main_entry -->'),
            strpos($result, 'class="item thoth_feature_video"')
        );
    }

    public function testDoesNotRenderUnsafeFeatureVideoUrl(): void
    {
        $filter = new FeatureVideoTemplateFilterStub(['url' => 'javascript:alert(1)']);
        $templateManager = new FeatureVideoTemplateManagerStub();
        $output = '<div class="main_entry"></div><!-- .main_entry -->';

        $filter->registerFilter($templateManager, 'frontend/pages/book.tpl');

        $this->assertSame($output, $templateManager->applyOutputFilter($output));
    }
}

class FeatureVideoTemplateManagerStub
{
    private FeatureVideoSubmissionStub $submission;
    private $outputFilter;

    public function __construct()
    {
        $this->submission = new FeatureVideoSubmissionStub();
    }

    public function getTemplateVars(string $name)
    {
        return $name === 'publishedSubmission' ? $this->submission : null;
    }

    public function registerFilter(string $type, $callback): void
    {
        $this->outputFilter = $callback;
    }

    public function applyOutputFilter(string $output): string
    {
        return $this->outputFilter ? call_user_func($this->outputFilter, $output) : $output;
    }
}

class FeatureVideoSubmissionStub
{
    public function getData(string $name)
    {
        return $name === 'thothWorkId' ? 'work-id' : null;
    }
}

class FeatureVideoTemplateFilterStub extends ThothFeatureVideoTemplateFilter
{
    private array $video;

    public function __construct(array $video)
    {
        $this->video = $video;
    }

    protected function loadVideo(string $workId): array
    {
        return $this->video;
    }
}
