<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');
/**
 * @file plugins/generic/thoth/tests/classes/services/ThothReferenceServiceTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothReferenceServiceTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothReferenceService
 *
 * @brief Test class for the ThothReferenceService class
 */

use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Inputs\PatchReference as ThothReference;

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.citation.CitationDAO');
import('lib.pkp.classes.db.DAOResultFactory');
import('classes.publication.Publication');
import('plugins.generic.thoth.classes.repositories.ThothReferenceRepository');
import('plugins.generic.thoth.classes.services.ThothReferenceService');

class ThothReferenceServiceTest extends PKPTestCase
{
    public function testRegisterReference()
    {
        $mockRepository = $this->getMockBuilder(ThothReferenceRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->setMethods(['add'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('add')
            ->will($this->returnValue('d667cd9c-27a8-44f8-b976-a0e867c0f607'));

        $mockCitation = $this->getMockBuilder(Citation::class)
            ->setMethods(['getSequence', 'getRawCitation'])
            ->getMock();
        $mockCitation->expects($this->once())
            ->method('getSequence')
            ->will($this->returnValue(1));
        $mockCitation->expects($this->once())
            ->method('getRawCitation')
            ->will($this->returnValue('Roe, Richard. (2019). A reference used in my book. Harvard University.'));

        $thothWorkId = '5e613aee-c27d-4ac8-b87e-cae5deb11771';

        $service = new ThothReferenceService($mockRepository);
        $thothReferenceId = $service->register($mockCitation, $thothWorkId);

        $this->assertSame('d667cd9c-27a8-44f8-b976-a0e867c0f607', $thothReferenceId);
    }

    public function testSynchronizeByPublicationUsesOmpReferences()
    {
        $citation = new Citation('Roe, Richard. A reference. https://doi.org/10.1234/EXAMPLE.');
        $citation->setSequence(1);
        $citationResult = $this->createMock(DAOResultFactory::class);
        $citationResult->method('toArray')->willReturn([$citation]);
        $citationDao = $this->createMock(CitationDAO::class);
        $citationDao->expects($this->once())
            ->method('getByPublicationId')
            ->with(99)
            ->willReturn($citationResult);
        DAORegistry::registerDAO('CitationDAO', $citationDao);

        $publication = $this->createMock(Publication::class);
        $publication->method('getId')->willReturn(99);
        $repository = $this->createMock(ThothReferenceRepository::class);
        $repository->method('new')->willReturnCallback(function ($data) {
            return new ThothReference($data);
        });
        $repository->expects($this->once())
            ->method('getByWorkId')
            ->with('work-id')
            ->willReturn([]);
        $repository->expects($this->once())
            ->method('add')
            ->with($this->callback(function (ThothReference $reference) {
                return $reference->getWorkId() === 'work-id'
                    && $reference->getReferenceOrdinal() === 1
                    && $reference->getDoi() === 'https://doi.org/10.1234/example'
                    && $reference->getUnstructuredCitation()
                        === 'Roe, Richard. A reference. https://doi.org/10.1234/EXAMPLE.';
            }));

        $service = new ThothReferenceService($repository);
        $service->synchronizeByPublication($publication, 'work-id');
    }

    public function testUpdateReconcilesReferences()
    {
        $repository = $this->createMock(ThothReferenceRepository::class);
        $repository->method('new')->willReturnCallback(function ($data) {
            return new ThothReference($data);
        });
        $repository->expects($this->once())
            ->method('edit')
            ->with($this->callback(function (ThothReference $reference) {
                return $reference->getReferenceId() === 'doi-reference-id'
                    && $reference->getWorkId() === 'work-id'
                    && $reference->getReferenceOrdinal() === 1
                    && $reference->getDoi() === 'https://doi.org/10.1234/example'
                    && $reference->getUnstructuredCitation() === 'Updated citation. doi:10.1234/example';
            }));
        $repository->expects($this->once())
            ->method('add')
            ->with($this->callback(function (ThothReference $reference) {
                return $reference->getWorkId() === 'work-id'
                    && $reference->getReferenceOrdinal() === 2
                    && $reference->getUnstructuredCitation() === 'A new reference.';
            }));
        $repository->expects($this->once())
            ->method('delete')
            ->with('removed-reference-id');

        $service = new ThothReferenceService($repository);
        $service->update([
            [
                'referenceOrdinal' => 1,
                'doi' => '10.1234/example',
                'unstructuredCitation' => 'Updated citation. doi:10.1234/example',
            ],
            [
                'referenceOrdinal' => 2,
                'unstructuredCitation' => 'A new reference.',
            ],
        ], 'work-id', [
            [
                'referenceId' => 'doi-reference-id',
                'referenceOrdinal' => 1,
                'doi' => 'https://doi.org/10.1234/EXAMPLE',
                'unstructuredCitation' => 'Previous citation. https://doi.org/10.1234/EXAMPLE',
            ],
            [
                'referenceId' => 'removed-reference-id',
                'referenceOrdinal' => 2,
                'unstructuredCitation' => 'A removed reference.',
            ],
        ]);
    }

    public function testUpdateSkipsEquivalentNormalizedCitation()
    {
        $repository = $this->createMock(ThothReferenceRepository::class);
        $repository->expects($this->never())->method('add');
        $repository->expects($this->never())->method('edit');
        $repository->expects($this->never())->method('delete');

        $service = new ThothReferenceService($repository);
        $service->update([
            [
                'referenceOrdinal' => 1,
                'unstructuredCitation' => 'Roe,  Richard. A Book.',
            ],
        ], 'work-id', [
            [
                'referenceId' => 'reference-id',
                'referenceOrdinal' => 1,
                'doi' => '10.1234/remote-metadata',
                'unstructuredCitation' => 'roe, richard. a book.',
            ],
        ]);
    }

    public function testUpdateReordersReferencesWithoutOrdinalCollisions()
    {
        $repository = $this->createMock(ThothReferenceRepository::class);
        $repository->method('new')->willReturnCallback(function ($data) {
            return new ThothReference($data);
        });
        $edits = [];
        $repository->expects($this->exactly(4))
            ->method('edit')
            ->willReturnCallback(function (ThothReference $reference) use (&$edits) {
                $edits[] = [$reference->getReferenceId(), $reference->getReferenceOrdinal()];
            });
        $repository->expects($this->never())->method('add');
        $repository->expects($this->never())->method('delete');

        $service = new ThothReferenceService($repository);
        $service->update([
            ['referenceOrdinal' => 1, 'unstructuredCitation' => 'Reference B.'],
            ['referenceOrdinal' => 2, 'unstructuredCitation' => 'Reference A.'],
        ], 'work-id', [
            [
                'referenceId' => 'reference-a-id',
                'referenceOrdinal' => 1,
                'unstructuredCitation' => 'Reference A.',
            ],
            [
                'referenceId' => 'reference-b-id',
                'referenceOrdinal' => 2,
                'unstructuredCitation' => 'Reference B.',
            ],
        ]);

        $this->assertSame([
            ['reference-b-id', 3],
            ['reference-a-id', 4],
            ['reference-b-id', 1],
            ['reference-a-id', 2],
        ], $edits);
    }
}
