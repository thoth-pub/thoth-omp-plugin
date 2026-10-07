<?php

/**
 * @file plugins/generic/thoth/tests/classes/notification/ThothNotificationTest.php
 *
 * Copyright (c) 2026 Lepidus Tecnologia
 * Copyright (c) 2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothNotificationTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @brief Verifies error causes and diagnostic context
 */


use PHPUnit\Framework\TestCase;
use ThothApi\Exception\QueryException;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../classes/notification/ThothNotification.inc.php';

class ThothNotificationTest extends TestCase
{
    public function testNotificationPreservesExceptionForActivityAndOperationDiagnostics(): void
    {
        $error = new QueryException(['message' => 'Permission denied'], 'mutation { createWork { workId } }');
        $notification = $this->getMockBuilder(ThothNotification::class)->onlyMethods(['notify'])->getMock();
        $notification->expects($this->once())->method('notify')->with(
            null,
            null,
            NOTIFICATION_TYPE_ERROR,
            'plugins.generic.thoth.register.error',
            $this->identicalTo($error)
        );
        $notification->notifyError(null, null, $error);
    }
}
