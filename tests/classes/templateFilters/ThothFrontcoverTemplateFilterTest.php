<?php

/**
 * @file plugins/generic/thoth/tests/classes/templateFilters/ThothFrontcoverTemplateFilterTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothFrontcoverTemplateFilterTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothFrontcoverTemplateFilter
 *
 * @brief Test class for the ThothFrontcoverTemplateFilter class
 */

namespace APP\plugins\generic\thoth\tests\classes\templateFilters;

use APP\plugins\generic\thoth\classes\templateFilters\ThothFrontcoverTemplateFilter;
use PKP\tests\PKPTestCase;

require_once __DIR__ . '/../../../vendor/autoload.php';

class ThothFrontcoverTemplateFilterTest extends PKPTestCase
{
    public function testReplacesBookPageCoverImageWithThothFrontcoverUrl(): void
    {
        $filter = new ThothFrontcoverTemplateFilter();
        $templateMgr = new ThothFrontcoverTemplateManagerStub('https://cdn.thoth.pub/frontcover.png');
        $output = '<div class="item cover"><img src="http://omp.test/public/presses/1/cover_t.jpg" alt=""></div>';

        $filter->registerFilter($templateMgr, 'frontend/pages/book.tpl');
        $result = $templateMgr->applyOutputFilter($output);

        $this->assertStringContainsString('src="https://cdn.thoth.pub/frontcover.png"', $result);
    }

    public function testKeepsBookPageCoverImageWhenThothFrontcoverUrlIsInvalid(): void
    {
        $filter = new ThothFrontcoverTemplateFilter();
        $templateMgr = new ThothFrontcoverTemplateManagerStub('javascript:alert(1)');
        $output = '<div class="item cover"><img src="http://omp.test/public/presses/1/cover_t.jpg" alt=""></div>';

        $filter->registerFilter($templateMgr, 'frontend/pages/book.tpl');
        $result = $templateMgr->applyOutputFilter($output);

        $this->assertSame($output, $result);
    }
}

class ThothFrontcoverTemplateManagerStub
{
    private ThothFrontcoverPublicationStub $publication;
    private $outputFilter = null;

    public function __construct(string $frontcoverUrl)
    {
        $this->publication = new ThothFrontcoverPublicationStub($frontcoverUrl);
    }

    public function getTemplateVars(string $name)
    {
        return $name === 'publication' ? $this->publication : null;
    }

    public function unregisterFilter($type, $callback): void
    {
    }

    public function registerFilter($type, $callback): void
    {
        if ($type === 'output') {
            $this->outputFilter = $callback;
        }
    }

    public function applyOutputFilter(string $output): string
    {
        return $this->outputFilter ? call_user_func($this->outputFilter, $output) : $output;
    }
}

class ThothFrontcoverPublicationStub
{
    private string $frontcoverUrl;

    public function __construct(string $frontcoverUrl)
    {
        $this->frontcoverUrl = $frontcoverUrl;
    }

    public function getData(string $name): ?string
    {
        return $name === 'thothFrontcoverUrl' ? $this->frontcoverUrl : null;
    }
}
