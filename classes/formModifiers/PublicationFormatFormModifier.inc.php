<?php

/**
 * @file plugins/generic/thoth/classes/formModifiers/PublicationFormatFormModifier.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PublicationFormatFormModifier
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Handles Thoth accessibility metadata in OMP publication format forms
 */

class PublicationFormatFormModifier
{
    public const ACCESSIBILITY_FIELDS = [
        'accessibilityStandard',
        'accessibilityAdditionalStandard',
        'accessibilityException',
        'accessibilityReportUrl',
    ];

    private $plugin;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    public function handleFormDisplay($hookName, $args)
    {
        $form = $args[0];
        $templateMgr = TemplateManager::getManager();
        $publicationFormat = $form->getPublicationFormat();

        foreach (self::ACCESSIBILITY_FIELDS as $fieldName) {
            if ($publicationFormat && $form->getData($fieldName) === null) {
                $form->setData($fieldName, $publicationFormat->getData($fieldName));
            }
        }

        $templateMgr->assign([
            'thothAccessibilityStandardOptions' => $this->getAccessibilityStandardOptions(),
            'thothAccessibilityExceptionOptions' => $this->getAccessibilityExceptionOptions(),
        ]);

        $filter = new PublicationFormatTemplateFilter($this->plugin);
        $filter->register($templateMgr);

        return false;
    }

    public function addAccessibilityFieldNames($hookName, $args)
    {
        $fieldNames = & $args[1];
        $fieldNames = array_values(array_unique(array_merge($fieldNames, self::ACCESSIBILITY_FIELDS)));

        return false;
    }

    public function handleFormReadUserVars($hookName, $args)
    {
        $vars = & $args[1];
        $vars = array_unique(array_merge($vars, self::ACCESSIBILITY_FIELDS));

        return false;
    }

    public function handleFormValidate($hookName, $args)
    {
        $form = $args[0];
        $reportUrl = trim((string) $form->getData('accessibilityReportUrl'));

        if ($reportUrl !== '' && filter_var($reportUrl, FILTER_VALIDATE_URL) === false) {
            $form->addError(
                'accessibilityReportUrl',
                __('plugins.generic.thoth.publicationFormat.accessibilityReportUrl.invalid')
            );
        }

        return false;
    }

    public function handleFormExecute($hookName, $args)
    {
        $form = $args[0];
        $publicationFormat = $form->getPublicationFormat();

        if (!$publicationFormat) {
            return false;
        }

        foreach (self::ACCESSIBILITY_FIELDS as $fieldName) {
            $publicationFormat->setData($fieldName, $this->normalizeOptionalValue($form->getData($fieldName)));
        }

        return false;
    }

    private function normalizeOptionalValue($value)
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function getAccessibilityStandardOptions()
    {
        return [
            '' => 'common.none',
            'WCAG21AA' => 'plugins.generic.thoth.publicationFormat.accessibilityStandard.wcag21aa',
            'WCAG21AAA' => 'plugins.generic.thoth.publicationFormat.accessibilityStandard.wcag21aaa',
            'WCAG22AA' => 'plugins.generic.thoth.publicationFormat.accessibilityStandard.wcag22aa',
            'WCAG22AAA' => 'plugins.generic.thoth.publicationFormat.accessibilityStandard.wcag22aaa',
            'EPUB_A11Y10AA' => 'plugins.generic.thoth.publicationFormat.accessibilityStandard.epubA11y10aa',
            'EPUB_A11Y10AAA' => 'plugins.generic.thoth.publicationFormat.accessibilityStandard.epubA11y10aaa',
            'EPUB_A11Y11AA' => 'plugins.generic.thoth.publicationFormat.accessibilityStandard.epubA11y11aa',
            'EPUB_A11Y11AAA' => 'plugins.generic.thoth.publicationFormat.accessibilityStandard.epubA11y11aaa',
            'PDF_UA1' => 'plugins.generic.thoth.publicationFormat.accessibilityStandard.pdfUa1',
            'PDF_UA2' => 'plugins.generic.thoth.publicationFormat.accessibilityStandard.pdfUa2',
        ];
    }

    private function getAccessibilityExceptionOptions()
    {
        return [
            '' => 'common.none',
            'MICRO_ENTERPRISES' => 'plugins.generic.thoth.publicationFormat.accessibilityException.microEnterprises',
            'DISPROPORTIONATE_BURDEN' => 'plugins.generic.thoth.publicationFormat.accessibilityException.disproportionateBurden',
            'FUNDAMENTAL_ALTERATION' => 'plugins.generic.thoth.publicationFormat.accessibilityException.fundamentalAlteration',
        ];
    }
}
