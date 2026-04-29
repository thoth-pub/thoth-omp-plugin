<?php

/**
 * @file plugins/generic/thoth/classes/templateFilters/PublicationFormatTemplateFilter.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PublicationFormatTemplateFilter
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Injects Thoth accessibility fields into OMP publication format forms
 */

namespace APP\plugins\generic\thoth\classes\templateFilters;

use PKP\plugins\GenericPlugin;

class PublicationFormatTemplateFilter
{
    private const FORMAT_FORM_ID = 'addPublicationFormatForm';
    private const ISBN_SECTION_END = '{/fbvFormSection}';

    private GenericPlugin $plugin;

    public function __construct(GenericPlugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function register($templateMgr): void
    {
        $templateMgr->registerFilter('output', $this->injectAccessibilityFields(...));
    }

    public function injectAccessibilityFields(string $output, $template): string
    {
        if (
            strpos($output, self::FORMAT_FORM_ID) === false
            || strpos($output, 'id="accessibilityStandard"') !== false
        ) {
            return $output;
        }

        $partial = $template->smarty->fetch(
            $this->plugin->getTemplateResource('publicationFormatAccessibilityFields.tpl')
        );

        $isbnTitle = __('grid.catalogEntry.isbn');
        $isbnTitlePosition = strpos($output, $isbnTitle);
        if ($isbnTitlePosition === false) {
            return $output;
        }

        $insertionPosition = strpos($output, '</fieldset>', $isbnTitlePosition);
        if ($insertionPosition === false) {
            return $output;
        }

        return substr_replace($output, $partial, $insertionPosition + strlen('</fieldset>'), 0);
    }
}
