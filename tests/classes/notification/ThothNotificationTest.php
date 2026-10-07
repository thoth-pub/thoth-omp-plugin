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

namespace APP\plugins\generic\thoth\tests\classes\notification;

use APP\plugins\generic\thoth\classes\notification\ThothNotification;
use PKP\notification\Notification;
use PKP\tests\PKPTestCase;
use ThothApi\Exception\QueryException;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../classes/notification/ThothNotification.php';

class ThothNotificationTest extends PKPTestCase
{
    public function testNotificationPreservesExceptionForActivityAndOperationDiagnostics(): void
    {
        $error = new QueryException(['message' => 'Permission denied'], 'mutation { createWork { workId } }');
        $notification = $this->getMockBuilder(ThothNotification::class)->onlyMethods(['notify'])->getMock();
        $notification->expects($this->once())->method('notify')->with(
            null,
            null,
            Notification::NOTIFICATION_TYPE_ERROR,
            'plugins.generic.thoth.register.error',
            $this->identicalTo($error)
        );
        $notification->notifyError(null, null, $error);
    }
    public function testNotificationPreservesQuotesAndHtmlAsPlainText(): void
    {
        $user = $this->createMock(\PKP\user\User::class);
        $user->method('getId')->willReturn(1);
        $request = $this->createMock(\APP\core\Request::class);
        $request->method('getUser')->willReturn($user);
        $notification = $this->getMockBuilder(ThothNotification::class)->onlyMethods(['logInfo'])->getMock();
        $error = new QueryException(['message' => 'Invalid "work" & <script>example</script>']);
        $notification->expects($this->once())->method('logInfo')->with(
            $request,
            null,
            'plugins.generic.thoth.register.error.log',
            $error
        );

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $notification->notifyError($request, null, $error);
            $contents = \Illuminate\Support\Facades\DB::table('notification_settings')
                ->where('setting_name', 'contents')->orderByDesc('notification_id')->value('setting_value');
            $this->assertSame(
                __('plugins.generic.thoth.register.error') . "\n" . 'Invalid "work" & <script>example</script>',
                $contents
            );
            $this->assertStringNotContainsString('&quot;', $contents);
        } finally {
            \Illuminate\Support\Facades\DB::rollBack();
        }
    }

}
