<?php

/**
 * @file plugins/generic/thoth/classes/templateFilters/PublicationFormatTemplateFilter.inc.php
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

class PublicationFormatTemplateFilter
{
    private const FORMAT_FORM_ID = 'addPublicationFormatForm';

    private $plugin;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    public function register($templateMgr)
    {
        $templateMgr->registerFilter('output', [$this, 'injectAccessibilityFields']);
    }

    public function injectAccessibilityFields($output, $template)
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

        $insertionPosition = $this->getInsertionPosition($output);
        if ($insertionPosition === false) {
            return $output;
        }

        return substr_replace($output, $partial, $insertionPosition, 0);
    }

    private function getInsertionPosition($output)
    {
        $isbnTitle = __('grid.catalogEntry.isbn');
        $isbnTitlePosition = strpos($output, $isbnTitle);
        if ($isbnTitlePosition !== false) {
            $fieldsetEndPosition = strpos($output, '</fieldset>', $isbnTitlePosition);
            if ($fieldsetEndPosition !== false) {
                return $fieldsetEndPosition + strlen('</fieldset>');
            }
        }

        return strpos($output, '<p><span class="formRequired">');
    }
}
