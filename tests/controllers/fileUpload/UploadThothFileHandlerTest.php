<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');

use PKP\core\PKPRequest;
use PKP\tests\PKPTestCase;

import('plugins.generic.thoth.controllers.fileUpload.UploadThothFileHandler');

class UploadThothFileHandlerTest extends PKPTestCase
{
    public function testTemporaryUploadRequiresValidCsrfToken(): void
    {
        $handler = new class () extends UploadThothFileHandler {
            public function isValidUploadRequestForTest($request)
            {
                return $this->isValidUploadRequest($request);
            }
        };
        $request = new class () {
            public function checkCSRF()
            {
                return false;
            }
        };

        self::assertFalse($handler->isValidUploadRequestForTest($request));
    }

    public function testAuthorizationPoliciesScopeUploadToSubmissionAndPublication(): void
    {
        $handler = new class () extends UploadThothFileHandler {
            public function getPoliciesForTest($request, &$args, $roleAssignments)
            {
                return $this->getAuthorizationPolicies($request, $args, $roleAssignments);
            }
        };
        $args = [];

        $policies = $handler->getPoliciesForTest($this->createMock(PKPRequest::class), $args, []);

        self::assertInstanceOf(SubmissionAccessPolicy::class, $policies[0]);
        self::assertInstanceOf(PublicationAccessPolicy::class, $policies[1]);
    }
}
