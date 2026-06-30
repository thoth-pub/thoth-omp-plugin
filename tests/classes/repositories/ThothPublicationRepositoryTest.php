<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');
/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothPublicationRepositoryTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothPublicationRepositoryTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothPublicationRepository
 *
 * @brief Test class for the ThothPublicationRepository class
 */

use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Enums\PublicationType;
use ThothApi\GraphQL\Inputs\PatchPublication as ThothPublication;
use ThothApi\GraphQL\Schemas\File as ThothFile;
use ThothApi\GraphQL\Schemas\Publication as ThothPublicationSchema;
use ThothApi\GraphQL\Schemas\Work as ThothWork;

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.repositories.ThothPublicationRepository');

class ThothPublicationRepositoryTest extends PKPTestCase
{
    public function testNewThothPublication()
    {
        $data = [
            'workId' => 'a2c032c6-b09b-4911-a67b-17f97cb57cc1',
            'publicationType' => PublicationType::PDF,
            'isbn' => '978-3-16-148410-0',
            'width' => '60',
            'height' => '120',
            'depth' => '20',
            'weight' => '80'
        ];

        $mockThothClient = $this->getMockBuilder(ThothClient::class)->getMock();
        $repository = new ThothPublicationRepository($mockThothClient);

        $thothPublication = $repository->new($data);

        $this->assertInstanceOf(ThothPublication::class, $thothPublication);
        $this->assertSame($data, $thothPublication->getAllData());
    }

    public function testGetPublication()
    {
        $expectedThothPublication = new ThothPublication([
            'publicationId' => '1bce4a08-270c-4515-b0d5-d72d001314d4',
            'workId' => 'a2c032c6-b09b-4911-a67b-17f97cb57cc1',
            'publicationType' => PublicationType::PDF,
            'isbn' => '978-3-16-148410-0',
            'width' => '60',
            'height' => '120',
            'depth' => '20',
            'weight' => '80'
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['publication'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('publication')
            ->will($this->returnValue($expectedThothPublication));

        $repository = new ThothPublicationRepository($mockThothClient);

        $thothPublication = $repository->get('1bce4a08-270c-4515-b0d5-d72d001314d4');

        $this->assertEquals($expectedThothPublication, $thothPublication);
    }

    public function testGetPublicationByType()
    {
        $expectedThothWork = new ThothWork([
            'publications' => [
                [
                    'publicationId' => 'efac5d7a-2284-4432-ad50-02b70aadec49',
                    'publicationType' => PublicationType::PDF,
                ]
            ]
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['work'])
            ->getMock();
        $mockThothClient->expects($this->once())
            ->method('work')
            ->with(
                'a2c032c6-b09b-4911-a67b-17f97cb57cc1',
                ['publications' => ['publicationId', 'publicationType']]
            )
            ->will($this->returnValue($expectedThothWork));

        $repository = new ThothPublicationRepository($mockThothClient);

        $thothPublicationId = $repository->getIdByType(
            'a2c032c6-b09b-4911-a67b-17f97cb57cc1',
            PublicationType::PDF
        );

        $this->assertEquals('efac5d7a-2284-4432-ad50-02b70aadec49', $thothPublicationId);
    }

    public function testGetFilesByWorkId()
    {
        $expectedThothFile = new ThothFile([
            'fileId' => '0c333e20-09f9-4f32-9f8f-20e801437dba',
            'cdnUrl' => 'https://example.thoth.pub/10.12345/book.pdf',
            'mimeType' => 'application/pdf',
            'objectKey' => '10.12345/book.pdf',
        ]);
        $expectedThothWork = new ThothWork([
            'publications' => [
                new ThothPublicationSchema([
                    'publicationId' => 'efac5d7a-2284-4432-ad50-02b70aadec49',
                    'publicationType' => PublicationType::PDF,
                    'file' => $expectedThothFile,
                ]),
                new ThothPublicationSchema([
                    'publicationId' => '7dbd7f38-64f8-4df2-a3dd-5ac4d6e7c1cb',
                    'publicationType' => PublicationType::EPUB,
                    'file' => null,
                ]),
            ]
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['work'])
            ->getMock();
        $mockThothClient->expects($this->once())
            ->method('work')
            ->with(
                'a2c032c6-b09b-4911-a67b-17f97cb57cc1',
                [
                    'workId',
                    'publications' => [
                        'publicationId',
                        'publicationType',
                        'file' => [
                            'fileId',
                            'cdnUrl',
                            'mimeType',
                            'objectKey',
                        ],
                    ],
                ]
            )
            ->will($this->returnValue($expectedThothWork));

        $repository = new ThothPublicationRepository($mockThothClient);

        $thothFiles = $repository->getFilesByWorkId('a2c032c6-b09b-4911-a67b-17f97cb57cc1');

        $this->assertSame([
            [
                'publicationType' => PublicationType::PDF,
                'file' => $expectedThothFile,
            ]
        ], $thothFiles);
    }

    public function testFindPublication()
    {
        $expectedThothPublication = new ThothPublication([
            'isbn' => '978-3-16-148410-0'
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['publications'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('publications')
            ->will($this->returnValue([$expectedThothPublication]));

        $repository = new ThothPublicationRepository($mockThothClient);

        $thothPublication = $repository->find('978-3-16-148410-0');

        $this->assertEquals($expectedThothPublication, $thothPublication);
    }

    public function testAddPublication()
    {
        $thothPublication = new ThothPublication([
            'workId' => 'a2c032c6-b09b-4911-a67b-17f97cb57cc1',
            'publicationType' => PublicationType::PDF,
            'isbn' => '978-3-16-148410-0',
            'width' => '60',
            'height' => '120',
            'depth' => '20',
            'weight' => '80'
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['createPublication'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('createPublication')
            ->will($this->returnValue('36fcfd7a-2284-4432-ad50-02b70aadec49'));

        $repository = new ThothPublicationRepository($mockThothClient);

        $thothPublicationId = $repository->add($thothPublication);

        $this->assertEquals('36fcfd7a-2284-4432-ad50-02b70aadec49', $thothPublicationId);
    }

    public function testEditPublication()
    {
        $thothPatchPublication = new ThothPublication([
            'publicationId' => 'fc6618f1-f4db-44f9-bbe3-75438f4bd536',
            'workId' => 'a2c032c6-b09b-4911-a67b-17f97cb57cc1',
            'publicationType' => PublicationType::EPUB,
            'isbn' => '978-3-16-148410-0'
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['updatePublication'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('updatePublication')
            ->will($this->returnValue('fc6618f1-f4db-44f9-bbe3-75438f4bd536'));

        $repository = new ThothPublicationRepository($mockThothClient);

        $thothPublicationId = $repository->edit($thothPatchPublication);

        $this->assertEquals('fc6618f1-f4db-44f9-bbe3-75438f4bd536', $thothPublicationId);
    }

    public function testDeletePublication()
    {
        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['deletePublication'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('deletePublication')
            ->will($this->returnValue('5f708d25-249a-4e67-aaf6-ce80b85ed2ee'));

        $repository = new ThothPublicationRepository($mockThothClient);

        $thothPublicationId = $repository->delete('5f708d25-249a-4e67-aaf6-ce80b85ed2ee');

        $this->assertEquals('5f708d25-249a-4e67-aaf6-ce80b85ed2ee', $thothPublicationId);
    }
}
