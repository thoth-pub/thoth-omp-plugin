<?php

/**
 * @file plugins/generic/thoth/classes/hooks/ThothMenuHandler.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothMenuHandler
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Injects the Thoth menu item into the backend navigation
 */

namespace APP\plugins\generic\thoth\classes\hooks;

use APP\core\Application;

class ThothMenuHandler
{
    public function addMenu($hookName, $args): bool
    {
        $templateMgr = $args[0];

        $request = Application::get()->getRequest();
        $router = $request->getRouter();
        $userRoles = (array) $router->getHandler()->getAuthorizedContextObject(Application::ASSOC_TYPE_USER_ROLES);

        $menu = $templateMgr->getState('menu');

        if (empty($menu) || !in_array(ROLE_ID_MANAGER, $userRoles)) {
            return false;
        }

        $offset = array_search('settings', array_keys($menu));

        if ($offset === false || count($menu) <= $offset) {
            $menu['thoth'] = [
                'name' => __('plugins.generic.thoth.navigation.thoth'),
                'url' => $router->url($request, null, 'thoth'),
                'isCurrent' => $router->getRequestedPage($request) === 'thoth',
                'icon' => 'Book',
            ];
        } else {
            $menu = array_slice($menu, 0, $offset, true) +
                [
                    'thoth' => [
                        'name' => __('plugins.generic.thoth.navigation.thoth'),
                        'url' => $router->url($request, null, 'thoth'),
                        'isCurrent' => $router->getRequestedPage($request) === 'thoth',
                        'icon' => 'Book',
                    ]
                ] +
                array_slice($menu, $offset, null, true);
        }

        $templateMgr->setState(['menu' => $menu]);

        return false;
    }
}
