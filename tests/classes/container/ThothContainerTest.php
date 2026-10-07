<?php

/**
 * @file plugins/generic/thoth/tests/classes/container/ThothContainerTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothContainerTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @brief Test class for the ThothContainer class
 */

namespace APP\plugins\generic\thoth\tests\classes\container;

use APP\plugins\generic\thoth\classes\container\ThothContainer;
use APP\plugins\generic\thoth\classes\facades\ThothService;
use PKP\tests\PKPTestCase;

class ThothContainerTest extends PKPTestCase
{
    protected function getMockedRegistryKeys(): array
    {
        return ['request'];
    }

    protected function getMockedDAOs(): array
    {
        return ['PluginSettingsDAO'];
    }

    public function testConfigurationAndClientsUseTheirOwnContext(): void
    {
        $settingsDao = $this->createMock(\PKP\plugins\PluginSettingsDAO::class);
        $settingsDao->method('getSetting')->willReturnCallback(fn ($contextId, $plugin, $name) => [
            'customThothApi' => false,
            'customThothApiUrl' => 'context-' . $contextId,
            'token' => '',
        ][$name]);
        \PKP\db\DAORegistry::registerDAO('PluginSettingsDAO', $settingsDao);
        $first = ThothContainer::getInstance(3001);
        $second = ThothContainer::getInstance(3002);

        $this->assertSame('context-3001', $first->get('config')['customThothApiUrl']);
        $this->assertSame('context-3002', $second->get('config')['customThothApiUrl']);
        $this->assertNotSame($first->get('client'), $second->get('client'));
        $this->assertSame($first->get('client'), $first->get('client'));
    }

    public function testGetSameContainerInstance()
    {
        $firstContainer = ThothContainer::getInstance(0);
        $secondContainer = ThothContainer::getInstance(0);

        $this->assertInstanceOf(ThothContainer::class, $firstContainer);
        $this->assertSame($firstContainer, $secondContainer);
    }

    public function testDoesNotReuseDependenciesAcrossContexts(): void
    {
        $first = ThothContainer::getInstance(101);
        $second = ThothContainer::getInstance(202);
        $first->singleton('contextMarker', fn () => 'first');
        $second->singleton('contextMarker', fn () => 'second');

        $this->assertSame('first', $first->get('contextMarker'));
        $this->assertSame('second', $second->get('contextMarker'));
        $this->assertSame($first, ThothContainer::getInstance(101));
    }

    public function testReplaceContainerBinding()
    {
        ThothContainer::getInstance(0)->set('foo', function () {
            return 'foo';
        });

        $fooFoo = ThothContainer::getInstance(0)->get('foo');

        ThothContainer::getInstance(0)->set('foo', function () {
            return 'bar';
        });

        $fooBar = ThothContainer::getInstance(0)->get('foo');

        $this->assertEquals('foo', $fooFoo);
        $this->assertEquals('bar', $fooBar);
    }

    public function testFeatureVideoSubmissionFacadeUsesContainerBinding(): void
    {
        $request = $this->createMock(\APP\core\Request::class);
        $request->method('getContext')->willReturn(null);
        \PKP\core\Registry::set('request', $request);
        $service = new \stdClass();
        ThothContainer::getInstance(0)->set('featureVideoSubmissionService', function () use ($service) {
            return $service;
        });

        $this->assertSame($service, ThothService::featureVideoSubmission());
    }
}
