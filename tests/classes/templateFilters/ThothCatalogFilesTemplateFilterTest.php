<?php

/**
 * @file plugins/generic/thoth/tests/classes/templateFilters/ThothCatalogFilesTemplateFilterTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothCatalogFilesTemplateFilterTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothCatalogFilesTemplateFilter
 *
 * @brief Test class for the ThothCatalogFilesTemplateFilter class
 */

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.templateFilters.ThothCatalogFilesTemplateFilter');

class ThothCatalogFilesTemplateFilterTest extends PKPTestCase
{
    public function testInjectChapterPlaceholdersAddsPlaceholderInsideChapterItem()
    {
        $chapter = new class () {
            public function getId()
            {
                return 42;
            }

            public function getLocalizedTitle()
            {
                return 'Chapter title';
            }
        };
        $templateMgr = new class ($chapter) {
            private $chapter;

            public function __construct($chapter)
            {
                $this->chapter = $chapter;
            }

            public function getTemplateVars($name)
            {
                return $name === 'chapters' ? [$this->chapter] : null;
            }

            public function unregisterFilter($type, $callback)
            {
            }
        };
        $output = '<ul><li><div class="title">Chapter title</div><div class="doi">DOI</div></li></ul>';
        $filter = new ThothCatalogFilesTemplateFilter();

        $filteredOutput = $filter->injectChapterPlaceholders($output, $templateMgr);

        $this->assertStringContainsString(
            '<div class="files thoth_files" data-thoth-target="chapter" data-chapter-id="42"></div>',
            $filteredOutput
        );
        $this->assertStringContainsString('<div class="doi">DOI</div>', $filteredOutput);
    }

    public function testInjectChapterPlaceholdersHandlesDuplicateChapterTitlesInOrder()
    {
        $chapters = [
            new class () {
                public function getId()
                {
                    return 10;
                }

                public function getLocalizedTitle()
                {
                    return 'Same title';
                }
            },
            new class () {
                public function getId()
                {
                    return 20;
                }

                public function getLocalizedTitle()
                {
                    return 'Same title';
                }
            },
        ];
        $templateMgr = new class ($chapters) {
            private $chapters;

            public function __construct($chapters)
            {
                $this->chapters = $chapters;
            }

            public function getTemplateVars($name)
            {
                return $name === 'chapters' ? $this->chapters : null;
            }

            public function unregisterFilter($type, $callback)
            {
            }
        };
        $output = '<ul><li><div class="title">Same title</div></li><li><div class="title">Same title</div></li></ul>';
        $filter = new ThothCatalogFilesTemplateFilter();

        $filteredOutput = $filter->injectChapterPlaceholders($output, $templateMgr);

        $this->assertMatchesRegularExpression(
            '/<li><div class="title">Same title<\/div><div class="files thoth_files" data-thoth-target="chapter" ' .
            'data-chapter-id="10"><\/div><\/li><li><div class="title">Same title<\/div><div class="files ' .
            'thoth_files" data-thoth-target="chapter" data-chapter-id="20"><\/div><\/li>/',
            $filteredOutput
        );
    }
}
