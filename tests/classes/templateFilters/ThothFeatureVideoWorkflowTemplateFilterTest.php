<?php

/**
 * @file plugins/generic/thoth/tests/classes/templateFilters/ThothFeatureVideoWorkflowTemplateFilterTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 */

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.templateFilters.ThothFeatureVideoWorkflowTemplateFilter');

use ThothApi\Exception\QueryException;

class ThothFeatureVideoWorkflowTemplateFilterTest extends PKPTestCase
{
    public function testAddsFeatureVideoTabAfterPublicationDates(): void
    {
        $filter = new FeatureVideoWorkflowTemplateFilterStub();
        $templateManager = new FeatureVideoWorkflowTemplateManagerStub();
        $output = '<tab id="publicationDates"><pkp-form /></tab></tabs>';

        $filter->registerFilter($templateManager, 'workflow/workflow.tpl');
        $result = $templateManager->applyOutputFilter($output);

        $this->assertSame(
            ['publicationDates', 'featureVideo'],
            $this->getTabIds($result)
        );
        $this->assertStringContainsString('v-bind="components.featureVideo"', $result);
    }

    public function testAddsOmpUploadFormToWorkflowComponents(): void
    {
        $filter = new FeatureVideoWorkflowTemplateFilterStub();
        $templateManager = new FeatureVideoWorkflowTemplateManagerStub();

        $filter->addFormConfig(
            new FeatureVideoWorkflowRequestStub(),
            $templateManager,
            'workflow/workflow.tpl'
        );

        $form = $templateManager->state['components']['featureVideo'];
        $videoField = array_values(array_filter($form['fields'], function ($field) {
            return $field['name'] === 'video';
        }))[0];
        $this->assertSame('api/temporaryFiles', $videoField['options']['url']);
        $this->assertSame('api/_submissions/12/featureVideo', $form['action']);
    }

    public function testKeepsWorkflowAvailableWhenFeatureVideoLookupFails(): void
    {
        $filter = new FeatureVideoLookupFailureWorkflowTemplateFilterStub();
        $templateManager = new FeatureVideoWorkflowTemplateManagerStub();

        $filter->addFormConfig(
            new FeatureVideoWorkflowRequestStub(),
            $templateManager,
            'workflow/workflow.tpl'
        );

        $form = $templateManager->state['components']['featureVideo'];
        $this->assertContains('video', array_column($form['fields'], 'name'));
    }

    public function testKeepsWorkflowAvailableWhenPermissionLookupFails(): void
    {
        $filter = new FeatureVideoPermissionFailureWorkflowTemplateFilterStub();
        $templateManager = new FeatureVideoWorkflowTemplateManagerStub();

        $filter->addFormConfig(
            new FeatureVideoWorkflowRequestStub(),
            $templateManager,
            'workflow/workflow.tpl'
        );

        $form = $templateManager->state['components']['featureVideo'];
        $this->assertSame(['permissionNotice'], array_column($form['fields'], 'name'));
    }

    private function getTabIds(string $output): array
    {
        preg_match_all('/<tab id="([^"]+)"/', $output, $matches);
        return $matches[1];
    }
}

class FeatureVideoWorkflowTemplateFilterStub extends ThothFeatureVideoWorkflowTemplateFilter
{
    protected function hasExistingVideo($submission)
    {
        return false;
    }

    protected function canUpload($request)
    {
        return true;
    }
}

class FeatureVideoLookupFailureWorkflowTemplateFilterStub extends ThothFeatureVideoWorkflowTemplateFilter
{
    protected function hasExistingVideo($submission)
    {
        throw new QueryException(
            ['message' => 'No record was found for the given ID.'],
            null,
            null,
            null,
            200
        );
    }

    protected function canUpload($request)
    {
        return true;
    }
}

class FeatureVideoPermissionFailureWorkflowTemplateFilterStub extends ThothFeatureVideoWorkflowTemplateFilter
{
    protected function hasExistingVideo($submission)
    {
        return false;
    }

    protected function canUpload($request)
    {
        throw new RuntimeException('Thoth is unavailable');
    }
}

class FeatureVideoWorkflowTemplateManagerStub
{
    public array $state = ['components' => []];
    private $outputFilter;

    public function registerFilter($type, $callback): void
    {
        $this->outputFilter = $callback;
    }

    public function getTemplateVars($name)
    {
        return new class () {
            public function getId(): int
            {
                return 12;
            }

            public function getData($name)
            {
                return null;
            }
        };
    }

    public function getState($name)
    {
        return $this->state[$name];
    }

    public function setState(array $state): void
    {
        $this->state = array_merge($this->state, $state);
    }

    public function applyOutputFilter(string $output): string
    {
        return call_user_func($this->outputFilter, $output, $this);
    }
}

class FeatureVideoWorkflowRequestStub
{
    public function getDispatcher()
    {
        return new class () {
            public function url($request, $route, $context, $path): string
            {
                return 'api/' . $path;
            }
        };
    }

    public function getContext()
    {
        return new class () {
            public function getData($name): string
            {
                return 'press';
            }
        };
    }
}
