<?php

/**
 * @file plugins/generic/thoth/tests/classes/templateFilters/PublicationFormatTemplateFilterTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PublicationFormatTemplateFilterTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see PublicationFormatTemplateFilter
 *
 * @brief Test class for the PublicationFormatTemplateFilter class
 */

namespace APP\plugins\generic\thoth\tests\classes\templateFilters;

require_once(__DIR__ . '/../../../vendor/autoload.php');

use APP\plugins\generic\thoth\classes\templateFilters\PublicationFormatTemplateFilter;
use PKP\plugins\GenericPlugin;
use PKP\tests\PKPTestCase;

class PublicationFormatTemplateFilterTest extends PKPTestCase
{
    public function testAccessibilityHelpPopoverIsAvailableInPartial(): void
    {
        $template = file_get_contents(__DIR__ . '/../../../templates/publicationFormatAccessibilityFields.tpl');

        $this->assertStringContainsString('thothAccessibilityHelpButton', $template);
        $this->assertStringContainsString('tooltipButton thothAccessibilityHelp__button', $template);
        $this->assertStringContainsString('fa fa-question-circle', $template);
        $this->assertStringContainsString('-screenReader', $template);
        $this->assertStringContainsString('aria-describedby="thothAccessibilityHelp"', $template);
        $this->assertStringContainsString('plugins.generic.thoth.publicationFormat.accessibilityHelp.description', $template);
    }

    public function testInjectAccessibilityFieldsAfterIsbnSection(): void
    {
        $plugin = $this->createMock(GenericPlugin::class);
        $plugin->method('getTemplateResource')->willReturn('publicationFormatAccessibilityFields.tpl');
        $filter = new PublicationFormatTemplateFilter($plugin);
        $template = new class () {
            public $smarty;

            public function __construct()
            {
                $this->smarty = new class () {
                    public function fetch($template)
                    {
                        return '<div id="accessibilityStandard">Accessibility</div>';
                    }
                };
            }
        };
        $output = '<form id="addPublicationFormatForm"><fieldset>ISBN</fieldset></form>';

        $filteredOutput = $filter->injectAccessibilityFields($output, $template);

        $this->assertStringContainsString('id="accessibilityStandard"', $filteredOutput);
        $this->assertGreaterThan(
            strpos($filteredOutput, '</fieldset>'),
            strpos($filteredOutput, 'id="accessibilityStandard"')
        );
    }
}
