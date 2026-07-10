<?php

namespace APP\plugins\generic\thoth\tests\classes\handlers\fileUpload;

require_once(__DIR__ . '/../../../../vendor/autoload.php');

use APP\plugins\generic\thoth\classes\handlers\fileUpload\UploadThothFileHandler;
use PKP\core\PKPRequest;
use PKP\security\authorization\PublicationAccessPolicy;
use PKP\security\authorization\SubmissionAccessPolicy;
use PKP\tests\PKPTestCase;

class UploadThothFileHandlerTest extends PKPTestCase
{
    public function testAuthorizationPoliciesScopeUploadToSubmissionAndPublication(): void
    {
        $handler = new class () extends UploadThothFileHandler {
            public function getPoliciesForTest($request, &$args, $roleAssignments): array
            {
                return $this->getAuthorizationPolicies($request, $args, $roleAssignments);
            }
        };
        $args = [];

        $policies = $handler->getPoliciesForTest(
            $this->createMock(PKPRequest::class),
            $args,
            []
        );

        self::assertInstanceOf(SubmissionAccessPolicy::class, $policies[0]);
        self::assertInstanceOf(PublicationAccessPolicy::class, $policies[1]);
    }
}
