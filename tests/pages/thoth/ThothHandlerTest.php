<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.pages.thoth.ThothHandler');

class ThothHandlerTest extends PKPTestCase
{
    public function testSubEditorListIsRestrictedToAssignedSubmissions(): void
    {
        $params = $this->getScopedParams([ROLE_ID_SUB_EDITOR]);

        self::assertSame([42], $params['assignedTo']);
    }

    public function testManagerListIsNotRestrictedToAssignments(): void
    {
        $params = $this->getScopedParams([ROLE_ID_MANAGER]);

        self::assertArrayNotHasKey('assignedTo', $params);
    }

    private function getScopedParams($roles)
    {
        $handler = new class () extends ThothHandler {
            public function scopeForTest($params, $request, $roles)
            {
                return $this->scopeParamsToUser($params, $request, $roles);
            }
        };
        $request = new class () {
            public function getUser()
            {
                return new class () {
                    public function getId()
                    {
                        return 42;
                    }
                };
            }
        };

        return $handler->scopeForTest([], $request, $roles);
    }
}
