<?php

namespace APP\plugins\generic\thoth\tests\classes\handlers\pages;

require_once(__DIR__ . '/../../../../vendor/autoload.php');

use APP\plugins\generic\thoth\classes\handlers\pages\ThothHandler;
use PKP\security\Role;
use PKP\tests\PKPTestCase;

class ThothHandlerTest extends PKPTestCase
{
    public function testSubEditorListIsRestrictedToAssignedSubmissions(): void
    {
        $collector = new class () {
            public array $assignedTo = [];

            public function assignedTo(array $userIds)
            {
                $this->assignedTo = $userIds;
                return $this;
            }
        };
        $request = $this->createRequest(42);
        $handler = new class () extends ThothHandler {
            public function scopeForTest($collector, $request, array $roles)
            {
                return $this->scopeCollectorToUser($collector, $request, $roles);
            }
        };

        $handler->scopeForTest($collector, $request, [Role::ROLE_ID_SUB_EDITOR]);

        self::assertSame([42], $collector->assignedTo);
    }

    public function testManagerListIsNotRestrictedToAssignments(): void
    {
        $collector = new class () {
            public array $assignedTo = [];

            public function assignedTo(array $userIds)
            {
                $this->assignedTo = $userIds;
                return $this;
            }
        };
        $handler = new class () extends ThothHandler {
            public function scopeForTest($collector, $request, array $roles)
            {
                return $this->scopeCollectorToUser($collector, $request, $roles);
            }
        };

        $handler->scopeForTest($collector, $this->createRequest(42), [Role::ROLE_ID_MANAGER]);

        self::assertSame([], $collector->assignedTo);
    }

    private function createRequest(int $userId)
    {
        return new class ($userId) {
            public function __construct(private int $userId)
            {
            }

            public function getUser()
            {
                return new class ($this->userId) {
                    public function __construct(private int $userId)
                    {
                    }

                    public function getId(): int
                    {
                        return $this->userId;
                    }
                };
            }
        };
    }
}
