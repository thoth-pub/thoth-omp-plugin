<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');

use PKP\tests\PKPTestCase;

import('plugins.generic.thoth.classes.api.ThothEndpoint');

class ThothEndpointTest extends PKPTestCase
{
    public function testSubmissionMustBelongToRequestContext(): void
    {
        $endpoint = new class () extends ThothEndpoint {
            public function isSubmissionInContextForTest($submission, $context)
            {
                return $this->isSubmissionInContext($submission, $context);
            }
        };
        $submission = $this->createObjectWithIdAndContext(10, 2);
        $sameContext = $this->createObjectWithIdAndContext(2, null);
        $differentContext = $this->createObjectWithIdAndContext(3, null);

        self::assertTrue($endpoint->isSubmissionInContextForTest($submission, $sameContext));
        self::assertFalse($endpoint->isSubmissionInContextForTest($submission, $differentContext));
        self::assertFalse($endpoint->isSubmissionInContextForTest(null, $sameContext));
    }

    private function createObjectWithIdAndContext($id, $contextId)
    {
        return new class ($id, $contextId) {
            private $id;
            private $contextId;

            public function __construct($id, $contextId)
            {
                $this->id = $id;
                $this->contextId = $contextId;
            }

            public function getId()
            {
                return $this->id;
            }

            public function getData($key)
            {
                return $key === 'contextId' ? $this->contextId : null;
            }
        };
    }
}
