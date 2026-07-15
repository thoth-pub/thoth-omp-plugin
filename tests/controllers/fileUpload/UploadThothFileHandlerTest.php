<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');

import('lib.pkp.classes.core.PKPRequest');
import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.controllers.fileUpload.UploadThothFileHandler');

class UploadThothFileHandlerTest extends PKPTestCase
{
    public function testTemporaryUploadIncludesCsrfToken(): void
    {
        $template = file_get_contents(
            dirname(__DIR__, 3) . '/templates/form/uploadThothPublicationFileForm.tpl'
        );

        self::assertMatchesRegularExpression(
            '/multipart_params:\s*\{ldelim\}\s*csrfToken:\s*\{csrf type="json"\}\s*\{rdelim\}/',
            $template
        );
    }

    public function testTemporaryUploadRequiresValidCsrfToken(): void
    {
        $handler = new class () extends UploadThothFileHandler {
            public function __construct()
            {
            }

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
            public function __construct()
            {
            }

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
