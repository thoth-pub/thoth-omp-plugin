<?php

/**
 * @file plugins/generic/thoth/tests/classes/hooks/ThothMenuHandlerTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 */

namespace APP\plugins\generic\thoth\tests\classes\hooks;

use APP\core\PageRouter;
use APP\core\Request;
use APP\plugins\generic\thoth\classes\hooks\ThothMenuHandler;
use APP\template\TemplateManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PKP\core\Registry;
use PKP\handler\PKPHandler;
use PKP\security\Role;
use PKP\tests\PKPTestCase;

class ThothMenuHandlerTest extends PKPTestCase
{
    protected function getMockedRegistryKeys(): array
    {
        return ['request'];
    }

    #[DataProvider('menuLayouts')]
    public function testThothMenuUsesItsLogoInsteadOfTheBookIcon(array $menu): void
    {
        $handler = $this->createMock(PKPHandler::class);
        $handler->method('getAuthorizedContextObject')->willReturn([Role::ROLE_ID_MANAGER]);
        $router = $this->createMock(PageRouter::class);
        $router->method('getHandler')->willReturn($handler);
        $router->method('url')->willReturn('/thoth');
        $router->method('getRequestedPage')->willReturn('thoth');
        $request = $this->createMock(Request::class);
        $request->method('getRouter')->willReturn($router);
        Registry::set('request', $request);

        $templateMgr = $this->createMock(TemplateManager::class);
        $templateMgr->method('getState')->with('menu')->willReturn($menu);
        $templateMgr->expects(self::once())->method('setState')->with(self::callback(function ($state) {
            self::assertSame('thoth-menu', $state['menu']['thoth']['class']);
            self::assertArrayNotHasKey('icon', $state['menu']['thoth']);
            self::assertSame('/thoth', $state['menu']['thoth']['url']);
            self::assertTrue($state['menu']['thoth']['isCurrent']);
            return true;
        }));

        (new ThothMenuHandler())->addMenu('TemplateManager::display', [$templateMgr]);
    }

    public static function menuLayouts(): array
    {
        return [
            'before settings' => [['submissions' => ['name' => 'Submissions'], 'settings' => ['name' => 'Settings']]],
            'without settings' => [['submissions' => ['name' => 'Submissions']]],
        ];
    }
}
