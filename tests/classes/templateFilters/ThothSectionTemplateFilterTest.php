<?php

/**
 * @file plugins/generic/thoth/tests/classes/templateFilters/ThothSectionTemplateFilterTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 */

namespace APP\plugins\generic\thoth\tests\classes\templateFilters;

require_once __DIR__ . '/../../../vendor/autoload.php';

use APP\plugins\generic\thoth\classes\templateFilters\ThothSectionTemplateFilter;
use PKP\tests\PKPTestCase;

class ThothSectionTemplateFilterTest extends PKPTestCase
{
    public function testProvidesFeatureVideoFormUrlToWorkflow(): void
    {
        $templateManager = new ThothWorkflowTemplateManagerStub();
        $filter = new ThothSectionTemplateFilter();

        $filter->addJavaScriptData(
            new ThothWorkflowRequestStub(),
            $templateManager,
            'dashboard/editors.tpl'
        );

        $this->assertStringContainsString(
            '"featureVideoUrl":"api\/_submissions\/__submissionId__\/featureVideo"',
            $templateManager->script
        );
    }

    public function testProvidesDedicatedSynchronizationUrlToWorkflow(): void
    {
        $templateManager = new ThothWorkflowTemplateManagerStub();
        $filter = new ThothSectionTemplateFilter();

        $filter->addJavaScriptData(
            new ThothWorkflowRequestStub(),
            $templateManager,
            'dashboard/editors.tpl'
        );

        $this->assertStringContainsString(
            '"synchronizeUrl":"api\/_submissions\/__submissionId__\/publications\/__publicationId__\/synchronize"',
            $templateManager->script
        );
    }

    public function testProvidesUnlinkUrlToWorkflow(): void
    {
        $templateManager = new ThothWorkflowTemplateManagerStub();
        $filter = new ThothSectionTemplateFilter();

        $filter->addJavaScriptData(
            new ThothWorkflowRequestStub(),
            $templateManager,
            'dashboard/editors.tpl'
        );

        $this->assertStringContainsString(
            '"unlinkUrl":"api\/_submissions\/__submissionId__\/thothWork"',
            $templateManager->script
        );
    }
}

class ThothWorkflowTemplateManagerStub
{
    public string $script = '';

    public function addJavaScript($name, $script, $options): void
    {
        $this->script = $script;
    }
}

class ThothWorkflowRequestStub
{
    public function getDispatcher(): ThothWorkflowDispatcherStub
    {
        return new ThothWorkflowDispatcherStub();
    }

    public function getContext(): ThothWorkflowContextStub
    {
        return new ThothWorkflowContextStub();
    }
}

class ThothWorkflowDispatcherStub
{
    public function url($request, $route, $context, $path): string
    {
        return ($route === ROUTE_API ? 'api/' : 'page/') . $path;
    }
}

class ThothWorkflowContextStub
{
    public function getData($name): string
    {
        return 'press';
    }
}
