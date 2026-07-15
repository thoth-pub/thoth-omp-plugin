<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');

/**
 * @file plugins/generic/thoth/tests/pages/thoth/ThothCatalogFilesHandlerTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothCatalogFilesHandlerTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothCatalogFilesHandler
 *
 * @brief Test class for the ThothCatalogFilesHandler class
 */

use ThothApi\GraphQL\Enums\PublicationType;
use ThothApi\GraphQL\Schemas\Work as ThothWork;

import('classes.publicationFormat.PublicationFormat');
import('lib.pkp.classes.db.DAOResultFactory');
import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.pages.thoth.ThothCatalogFilesHandler');

class ThothCatalogFilesHandlerTest extends PKPTestCase
{
    public function testGetThothWorkIdAcceptsString()
    {
        $handler = new ThothCatalogFilesHandler();
        $method = new ReflectionMethod($handler, 'getThothWorkId');
        $method->setAccessible(true);

        $thothWorkId = $method->invoke($handler, 'f26a15a1-e810-4eb5-b206-764974e654e7');

        $this->assertSame('f26a15a1-e810-4eb5-b206-764974e654e7', $thothWorkId);
    }

    public function testGetThothWorkIdAcceptsObject()
    {
        $handler = new ThothCatalogFilesHandler();
        $method = new ReflectionMethod($handler, 'getThothWorkId');
        $method->setAccessible(true);
        $thothWork = new ThothWork([
            'workId' => 'f26a15a1-e810-4eb5-b206-764974e654e7',
        ]);

        $thothWorkId = $method->invoke($handler, $thothWork);

        $this->assertSame('f26a15a1-e810-4eb5-b206-764974e654e7', $thothWorkId);
    }

    public function testAddRepresentationIdsMatchesPublicationType()
    {
        $handler = new ThothCatalogFilesHandler();
        $method = new ReflectionMethod($handler, 'addRepresentationIds');
        $method->setAccessible(true);
        $publication = $this->getPublicationWithFormats([
            $this->getPublicationFormat(35, 'PDF'),
        ]);

        $files = $method->invoke($handler, [[
            'url' => 'https://testcdn1.thoth.pub/book.pdf',
            'label' => 'PDF',
            'publicationType' => PublicationType::PDF,
        ]], $publication);

        $this->assertSame(35, $files[0]['representationId']);
    }

    public function testAddRepresentationIdsIgnoresUnmatchedPublicationType()
    {
        $handler = new ThothCatalogFilesHandler();
        $method = new ReflectionMethod($handler, 'addRepresentationIds');
        $method->setAccessible(true);
        $publication = $this->getPublicationWithFormats([
            $this->getPublicationFormat(35, 'EPUB'),
        ]);

        $files = $method->invoke($handler, [[
            'url' => 'https://testcdn1.thoth.pub/book.pdf',
            'label' => 'PDF',
            'publicationType' => PublicationType::PDF,
        ]], $publication);

        $this->assertArrayNotHasKey('representationId', $files[0]);
    }

    public function testIsPublicCatalogPublicationRejectsPublicationFromAnotherContext()
    {
        $handler = new ThothCatalogFilesHandler();
        $method = new ReflectionMethod($handler, 'isPublicCatalogPublication');
        $method->setAccessible(true);

        $isPublic = $method->invoke(
            $handler,
            $this->getSubmission(20, 2),
            $this->getPublication(20, STATUS_PUBLISHED),
            $this->getRequest(1)
        );

        $this->assertFalse($isPublic);
    }

    public function testIsPublicCatalogPublicationAcceptsPublishedPublicationFromCurrentContext()
    {
        $handler = new ThothCatalogFilesHandler();
        $method = new ReflectionMethod($handler, 'isPublicCatalogPublication');
        $method->setAccessible(true);

        $isPublic = $method->invoke(
            $handler,
            $this->getSubmission(20, 1),
            $this->getPublication(20, STATUS_PUBLISHED),
            $this->getRequest(1)
        );

        $this->assertTrue($isPublic);
    }

    private function getPublicationWithFormats($publicationFormats)
    {
        return new class ($publicationFormats) {
            private $publicationFormats;

            public function __construct($publicationFormats)
            {
                $this->publicationFormats = $publicationFormats;
            }

            public function getData($key)
            {
                return $key === 'publicationFormats' ? $this->publicationFormats : null;
            }
        };
    }

    private function getPublicationFormat($id, $localizedName)
    {
        $emptyResult = $this->getMockBuilder(DAOResultFactory::class)
            ->setMethods(['toArray'])
            ->disableOriginalConstructor()
            ->getMock();
        $emptyResult->expects($this->any())
            ->method('toArray')
            ->will($this->returnValue([]));

        $publicationFormat = $this->getMockBuilder(PublicationFormat::class)
            ->setMethods([
                'getId',
                'getEntryKey',
                'getLocalizedName',
                'getIdentificationCodes',
                'getRemoteUrl',
                'getData',
            ])
            ->getMock();
        $publicationFormat->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));
        $publicationFormat->expects($this->any())
            ->method('getEntryKey')
            ->will($this->returnValue('DA'));
        $publicationFormat->expects($this->any())
            ->method('getLocalizedName')
            ->will($this->returnValue($localizedName));
        $publicationFormat->expects($this->any())
            ->method('getIdentificationCodes')
            ->will($this->returnValue($emptyResult));
        $publicationFormat->expects($this->any())
            ->method('getRemoteUrl')
            ->will($this->returnValue(null));
        $publicationFormat->expects($this->any())
            ->method('getData')
            ->will($this->returnValue(null));

        return $publicationFormat;
    }

    private function getSubmission($id, $contextId)
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

    private function getPublication($submissionId, $status)
    {
        return new class ($submissionId, $status) {
            private $submissionId;
            private $status;

            public function __construct($submissionId, $status)
            {
                $this->submissionId = $submissionId;
                $this->status = $status;
            }

            public function getData($key)
            {
                $data = [
                    'submissionId' => $this->submissionId,
                    'status' => $this->status,
                ];

                return $data[$key] ?? null;
            }
        };
    }

    private function getRequest($contextId)
    {
        return new class ($contextId) {
            private $context;

            public function __construct($contextId)
            {
                $this->context = new class ($contextId) {
                    private $contextId;

                    public function __construct($contextId)
                    {
                        $this->contextId = $contextId;
                    }

                    public function getId()
                    {
                        return $this->contextId;
                    }
                };
            }

            public function getContext()
            {
                return $this->context;
            }
        };
    }
}
