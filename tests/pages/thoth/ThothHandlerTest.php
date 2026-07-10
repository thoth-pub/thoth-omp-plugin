<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');

use PKP\security\Role;
use PKP\tests\PKPTestCase;

import('plugins.generic.thoth.pages.thoth.ThothHandler');

class ThothHandlerTest extends PKPTestCase
{
    public function testSubEditorListIsRestrictedToAssignedSubmissions(): void
    {
        $collector = new class () {
            public $assignedTo = [];

            public function assignedTo($userIds)
            {
                $this->assignedTo = $userIds;
                return $this;
            }
        };
        $handler = new class () extends ThothHandler {
            public function scopeForTest($collector, $request, $roles)
            {
                return $this->scopeCollectorToUser($collector, $request, $roles);
            }
        };

        $handler->scopeForTest($collector, $this->createRequest(42), [Role::ROLE_ID_SUB_EDITOR]);

        self::assertSame([42], $collector->assignedTo);
    }

    public function testManagerListIsNotRestrictedToAssignments(): void
    {
        $collector = new class () {
            public $assignedTo = [];

            public function assignedTo($userIds)
            {
                $this->assignedTo = $userIds;
                return $this;
            }
        };
        $handler = new class () extends ThothHandler {
            public function scopeForTest($collector, $request, $roles)
            {
                return $this->scopeCollectorToUser($collector, $request, $roles);
            }
        };

        $handler->scopeForTest($collector, $this->createRequest(42), [Role::ROLE_ID_MANAGER]);

        self::assertSame([], $collector->assignedTo);
    }

    private function createRequest($userId)
    {
        return new class ($userId) {
            private $userId;

            public function __construct($userId)
            {
                $this->userId = $userId;
            }

            public function getUser()
            {
                return new class ($this->userId) {
                    private $userId;

                    public function __construct($userId)
                    {
                        $this->userId = $userId;
                    }

                    public function getId()
                    {
                        return $this->userId;
                    }
                };
            }
        };
    }
}
