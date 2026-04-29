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

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.templateFilters.PublicationFormatTemplateFilter');

class PublicationFormatTemplateFilterTest extends PKPTestCase
{
    public function testAccessibilityHelpPopoverIsAvailableInPartial()
    {
        $template = file_get_contents(__DIR__ . '/../../../templates/publicationFormatAccessibilityFields.tpl');

        $this->assertStringContainsString('thothAccessibilityHelpButton', $template);
        $this->assertStringContainsString('tooltipButton thothAccessibilityHelp__button', $template);
        $this->assertStringContainsString('fa fa-question-circle', $template);
        $this->assertStringContainsString('-screenReader', $template);
        $this->assertStringContainsString('aria-describedby="thothAccessibilityHelp"', $template);
        $this->assertStringContainsString('plugins.generic.thoth.publicationFormat.accessibilityHelp.description', $template);
    }

    public function testInjectAccessibilityFieldsBeforeRequiredFieldWhenIsbnSectionIsAbsent()
    {
        $filter = new PublicationFormatTemplateFilter(new class () {
            public function getTemplateResource($template)
            {
                return $template;
            }
        });
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
        $output = '<form id="addPublicationFormatForm"><fieldset>Format details</fieldset>'
            . '<p><span class="formRequired">Required</span></p></form>';

        $filteredOutput = $filter->injectAccessibilityFields($output, $template);

        $this->assertStringContainsString('id="accessibilityStandard"', $filteredOutput);
        $this->assertLessThan(
            strpos($filteredOutput, '<p><span class="formRequired">'),
            strpos($filteredOutput, 'id="accessibilityStandard"')
        );
    }
}
