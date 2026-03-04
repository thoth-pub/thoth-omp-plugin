<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothPublicationRepositoryTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothPublicationRepositoryTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothPublicationRepository
 *
 * @brief Test class for the ThothPublicationRepository class
 */

namespace APP\plugins\generic\thoth\tests\classes\repositories;

use APP\plugins\generic\thoth\classes\repositories\ThothPublicationRepository;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Models\Publication as ThothPublication;

class ThothPublicationRepositoryTest extends PKPTestCase
{
    public function testNewThothPublication()
    {
        $data = [
            'workId' => 'a2c032c6-b09b-4911-a67b-17f97cb57cc1',
            'publicationType' => ThothPublication::PUBLICATION_TYPE_PDF,
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
            'publicationType' => ThothPublication::PUBLICATION_TYPE_PDF,
            'isbn' => '978-3-16-148410-0',
            'width' => '60',
            'height' => '120',
            'depth' => '20',
            'weight' => '80'
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->onlyMethods(['publication'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('publication')
            ->willReturn($expectedThothPublication);

        $repository = new ThothPublicationRepository($mockThothClient);

        $thothPublication = $repository->get('1bce4a08-270c-4515-b0d5-d72d001314d4');

        $this->assertEquals($expectedThothPublication, $thothPublication);
    }

    public function testGetPublicationIdByType()
    {
        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->onlyMethods(['rawQuery'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('rawQuery')
            ->willReturn([
                'work' => ['publications' => [
                    [
                        'publicationId' => 'efac5d7a-2284-4432-ad50-02b70aadec49',
                    ]
                ]]
            ]);

        $repository = new ThothPublicationRepository($mockThothClient);

        $thothPublicationId = $repository->getIdByType(
            'a2c032c6-b09b-4911-a67b-17f97cb57cc1',
            ThothPublication::PUBLICATION_TYPE_PDF
        );

        $this->assertEquals('efac5d7a-2284-4432-ad50-02b70aadec49', $thothPublicationId);
    }

    public function testFindPublication()
    {
        $expectedThothPublication = new ThothPublication([
            'isbn' => '978-3-16-148410-0'
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->onlyMethods(['publications'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('publications')
            ->willReturn([$expectedThothPublication]);

        $repository = new ThothPublicationRepository($mockThothClient);

        $thothPublication = $repository->find('978-3-16-148410-0');

        $this->assertEquals($expectedThothPublication, $thothPublication);
    }

    public function testAddPublication()
    {
        $thothPublication = new ThothPublication([
            'workId' => 'a2c032c6-b09b-4911-a67b-17f97cb57cc1',
            'publicationType' => ThothPublication::PUBLICATION_TYPE_PDF,
            'isbn' => '978-3-16-148410-0',
            'width' => '60',
            'height' => '120',
            'depth' => '20',
            'weight' => '80'
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->onlyMethods(['createPublication'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('createPublication')
            ->willReturn('36fcfd7a-2284-4432-ad50-02b70aadec49');

        $repository = new ThothPublicationRepository($mockThothClient);

        $thothPublicationId = $repository->add($thothPublication);

        $this->assertEquals('36fcfd7a-2284-4432-ad50-02b70aadec49', $thothPublicationId);
    }

    public function testEditPublication()
    {
        $thothPatchPublication = new ThothPublication([
            'publicationId' => 'fc6618f1-f4db-44f9-bbe3-75438f4bd536',
            'workId' => 'a2c032c6-b09b-4911-a67b-17f97cb57cc1',
            'publicationType' => ThothPublication::PUBLICATION_TYPE_EPUB,
            'isbn' => '978-3-16-148410-0'
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->onlyMethods(['updatePublication'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('updatePublication')
            ->willReturn('fc6618f1-f4db-44f9-bbe3-75438f4bd536');

        $repository = new ThothPublicationRepository($mockThothClient);

        $thothPublicationId = $repository->edit($thothPatchPublication);

        $this->assertEquals('fc6618f1-f4db-44f9-bbe3-75438f4bd536', $thothPublicationId);
    }

    public function testDeletePublication()
    {
        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->onlyMethods(['deletePublication'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('deletePublication')
            ->willReturn('5f708d25-249a-4e67-aaf6-ce80b85ed2ee');

        $repository = new ThothPublicationRepository($mockThothClient);

        $thothPublicationId = $repository->delete('5f708d25-249a-4e67-aaf6-ce80b85ed2ee');

        $this->assertEquals('5f708d25-249a-4e67-aaf6-ce80b85ed2ee', $thothPublicationId);
    }
}
