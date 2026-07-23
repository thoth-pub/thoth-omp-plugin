<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothWorkRelationRepositoryTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothWorkRelationRepositoryTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothWorkRelationRepository
 *
 * @brief Test class for the ThothWorkRelationRepository class
 */

namespace APP\plugins\generic\thoth\tests\classes\repositories;

require_once(__DIR__ . '/../../../vendor/autoload.php');

use APP\plugins\generic\thoth\classes\repositories\ThothWorkRelationRepository;
use Mockery;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Enums\RelationType;
use ThothApi\GraphQL\Inputs\PatchWorkRelation as ThothWorkRelation;
use ThothApi\GraphQL\Schemas\Work as ThothWork;

class ThothWorkRelationRepositoryTest extends PKPTestCase
{
    public function testNewThothWorkRelation()
    {
        $data = [
            'relatorWorkId' => '074bef90-23f6-4e16-b188-175a1a428157',
            'relatedId' => '205d40f6-67d3-4af0-907b-ff53d02231cb',
            'relationType' => RelationType::HAS_CHILD,
            'relationOrdinal' => 1
        ];

        $mockThothClient = $this->getMockBuilder(ThothClient::class)->getMock();
        $repository = new ThothWorkRelationRepository($mockThothClient);

        $thothWorkRelation = $repository->new($data);

        $this->assertInstanceOf(ThothWorkRelation::class, $thothWorkRelation);
        $this->assertSame($data, $thothWorkRelation->getAllData());
    }

    public function testGetChaptersByWorkId(): void
    {
        $expectedWork = new ThothWork([
            'imprintId' => 'imprint-id',
            'relations' => [],
        ]);
        $mockThothClient = Mockery::mock(ThothClient::class);
        $mockThothClient->shouldReceive('work')
            ->once()
            ->with('book-id', Mockery::on(function (array $selection): bool {
                $chapterSelection = $selection['relations']['relatedWork'] ?? [];

                return in_array('imprintId', $selection, true)
                    && in_array('workRelationId', $selection['relations'], true)
                    && in_array('doi', $chapterSelection, true)
                    && isset($chapterSelection['titles'])
                    && isset($chapterSelection['abstracts'])
                    && isset($chapterSelection['contributions'])
                    && isset($chapterSelection['publications']);
            }))
            ->andReturn($expectedWork);

        $repository = new ThothWorkRelationRepository($mockThothClient);

        $this->assertSame($expectedWork->toArray(), $repository->getByWorkId('book-id'));
    }

    public function testAddWorkRelation()
    {
        $thothWorkRelation = new ThothWorkRelation([
            'relatorWorkId' => 'b23ffa4e-b50b-48c7-bdad-f331cb8449f7',
            'relatedId' => '205d40f6-67d3-4af0-907b-ff53d02231cb',
            'relationType' => RelationType::HAS_CHILD,
            'relationOrdinal' => 1
        ]);

        $mockThothClient = Mockery::mock(ThothClient::class);
        $mockThothClient->shouldReceive('createWorkRelation')
            ->zeroOrMoreTimes()
            ->andReturn('bded83ea-19f9-4c6d-a249-682d6c5bad5d');
        $repository = new ThothWorkRelationRepository($mockThothClient);

        $thothWorkRelationId = $repository->add($thothWorkRelation);

        $this->assertEquals('bded83ea-19f9-4c6d-a249-682d6c5bad5d', $thothWorkRelationId);
    }

    public function testEditWorkRelation()
    {
        $thothPatchWorkRelation = new ThothWorkRelation([
            'workRelationId' => '98d1efa2-475b-4cde-a180-d4fe8a470d25',
            'relatorWorkId' => 'b23ffa4e-b50b-48c7-bdad-f331cb8449f7',
            'relatedId' => '205d40f6-67d3-4af0-907b-ff53d02231cb',
            'relationType' => RelationType::HAS_TRANSLATION,
            'relationOrdinal' => 3
        ]);

        $mockThothClient = Mockery::mock(ThothClient::class);
        $mockThothClient->shouldReceive('updateWorkRelation')
            ->zeroOrMoreTimes()
            ->andReturn('98d1efa2-475b-4cde-a180-d4fe8a470d25');
        $repository = new ThothWorkRelationRepository($mockThothClient);

        $thothWorkRelationId = $repository->edit($thothPatchWorkRelation);

        $this->assertEquals('98d1efa2-475b-4cde-a180-d4fe8a470d25', $thothWorkRelationId);
    }

    public function testDeleteWorkRelation()
    {
        $mockThothClient = Mockery::mock(ThothClient::class);

        $mockThothClient->shouldReceive('deleteWorkRelation')
            ->zeroOrMoreTimes()
            ->andReturn('0dce314b-83d6-4663-be58-ac41e1ebae20');
        $repository = new ThothWorkRelationRepository($mockThothClient);

        $thothWorkRelationId = $repository->delete('0dce314b-83d6-4663-be58-ac41e1ebae20');

        $this->assertEquals('0dce314b-83d6-4663-be58-ac41e1ebae20', $thothWorkRelationId);
    }
}
