<?php

/**
 * @file plugins/generic/thoth/tests/classes/hooks/HookRegistrantTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class HookRegistrantTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @brief Test class for the HookRegistrant class
 */

namespace APP\plugins\generic\thoth\tests\classes\hooks;

use APP\core\Request;
use APP\plugins\generic\thoth\classes\container\ThothContainer;
use APP\plugins\generic\thoth\classes\hooks\HookRegistrant;
use PKP\core\PKPBaseController;
use PKP\core\Registry;
use PKP\handler\APIHandler;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use PKP\tests\PKPTestCase;

class HookRegistrantTest extends PKPTestCase
{
    protected function getMockedRegistryKeys(): array
    {
        return ['hooks', 'request'];
    }

    public function testRegistersApiRoutesThroughCoreHookWithoutLoadingThothClient(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getContext')->willReturn(null);
        Registry::set('request', $request);
        $container = ThothContainer::getInstance(0);
        $binding = $container->backup('client');
        $container->set('client', function () {
            self::fail('Registering OMP routes must not load the Thoth client.');
        });
        $handler = $this->getMockBuilder(APIHandler::class)
            ->disableOriginalConstructor()->onlyMethods(['addRoute'])->getMock();
        $handler->expects($this->exactly(6))->method('addRoute');
        $registrant = new HookRegistrant($this->createMock(GenericPlugin::class));
        $registrant->register();

        try {
            self::assertFalse(Hook::run('APIHandler::endpoints::_submissions', [
                $this->createMock(PKPBaseController::class),
                $handler,
            ]));
        } finally {
            $container->set('client', $binding);
        }
    }
}
