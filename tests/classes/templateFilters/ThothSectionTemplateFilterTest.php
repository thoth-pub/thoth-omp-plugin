<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');

use PKP\tests\PKPTestCase;

import('plugins.generic.thoth.classes.templateFilters.ThothSectionTemplateFilter');

class ThothSectionTemplateFilterTest extends PKPTestCase
{
    public function testProvidesDedicatedSynchronizationUrlToWorkflow()
    {
        $templateManager = new ThothSectionTemplateManagerStub();
        $filter = new ThothSectionTemplateFilter();

        $filter->addJavaScriptData(new ThothSectionRequestStub(), $templateManager, 'workflow/workflow.tpl');

        $this->assertStringContainsString(
            '"synchronizeUrl":"api\/_submissions\/13\/publications\/__publicationId__\/synchronize"',
            $templateManager->script
        );
    }
}

class ThothSectionTemplateManagerStub
{
    public $script = '';

    public function getTemplateVars($name)
    {
        return new ThothSectionSubmissionStub();
    }

    public function addJavaScript($name, $script, $options)
    {
        $this->script = $script;
    }
}

class ThothSectionSubmissionStub
{
    public function getId()
    {
        return 13;
    }
}

class ThothSectionRequestStub
{
    public function getDispatcher()
    {
        return new ThothSectionDispatcherStub();
    }

    public function getContext()
    {
        return new ThothSectionContextStub();
    }
}

class ThothSectionDispatcherStub
{
    public function url($request, $route, $context, $path)
    {
        return ($route === ROUTE_API ? 'api/' : 'page/') . $path;
    }
}

class ThothSectionContextStub
{
    public function getData($name)
    {
        return 'press';
    }
}
