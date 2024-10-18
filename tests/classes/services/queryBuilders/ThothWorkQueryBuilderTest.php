<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/queryBuilders/ThothWorkQueryBuilderTest.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothWorkQueryBuilderTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothWorkQueryBuilder
 *
 * @brief Test class for the ThothWorkQueryBuilder class
 */

use PKP\tests\PKPTestCase;

import('plugins.generic.thoth.classes.services.queryBuilders.ThothWorkQueryBuilder');
import('plugins.generic.thoth.lib.thothAPI.ThothClient');

class ThothWorkQueryBuilderTest extends PKPTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->expectedFields = [
            'workId',
            'workType',
            'workStatus',
            'fullTitle',
            'title',
            'subtitle',
            'edition',
            'imprintId',
            'doi',
            'publicationDate',
            'pageCount',
            'license',
            'copyrightHolder',
            'landingPage',
            'longAbstract',
            'coverUrl'
        ];

        $json = file_get_contents(__DIR__ . '/../../../fixtures/workWithContributions.json');
        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods([
                'query',
            ])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('query')
            ->will($this->returnValue(json_decode($json, true)['data']['work']));

        $this->queryBuilder = new ThothWorkQueryBuilder($mockThothClient);
    }

    protected function tearDown(): void
    {
        unset($this->queryBuilder);
        parent::tearDown();
    }

    public function testIncludeContributionsToQuery()
    {
        $this->expectedFields['contributions'] = [
            'contributionId',
            'workId',
            'contributorId',
            'contributionType',
            'mainContribution',
            'contributionOrdinal',
            'firstName',
            'lastName',
            'fullName',
            'biography'
        ];
        $this->queryBuilder->includeContributions();
        $properties = $this->getProperties($this->queryBuilder);
        $this->assertEquals($this->expectedFields, $properties['fields']);

        $this->expectedFields['contributions']['contributor'] = [
            'contributorId',
            'firstName',
            'lastName',
            'fullName',
            'orcid',
            'website'
        ];
        $this->queryBuilder->includeContributions(true);
        $properties = $this->getProperties($this->queryBuilder);
        $this->assertEquals($this->expectedFields, $properties['fields']);

        $this->expectedFields['contributions']['affiliations'] = [
            'affiliationId',
            'contributionId',
            'institutionId',
            'affiliationOrdinal'
        ];
        $this->queryBuilder->includeContributions(true, true);
        $properties = $this->getProperties($this->queryBuilder);
        $this->assertEquals($this->expectedFields, $properties['fields']);

        unset($this->expectedFields['contributions']['contributor']);
        $this->queryBuilder->includeContributions(false, true);
        $properties = $this->getProperties($this->queryBuilder);
        $this->assertEquals($this->expectedFields, $properties['fields']);
    }

    public function testIncludeRelationsToQuery()
    {
        $this->expectedFields['relations'] = [
            'workRelationId',
            'relatorWorkId',
            'relatedWorkId',
            'relationType',
            'relationOrdinal'
        ];
        $this->queryBuilder->includeRelations();
        $properties = $this->getProperties($this->queryBuilder);
        $this->assertEquals($this->expectedFields, $properties['fields']);

        $this->expectedFields['relations']['relatedWork'] = [
            'workId',
            'workType',
            'workStatus',
            'fullTitle',
            'title',
            'subtitle',
            'edition',
            'imprintId',
            'doi',
            'publicationDate',
            'pageCount',
            'license',
            'copyrightHolder',
            'landingPage',
            'longAbstract',
            'coverUrl'
        ];
        $this->queryBuilder->includeRelations(true);
        $properties = $this->getProperties($this->queryBuilder);
        $this->assertEquals($this->expectedFields, $properties['fields']);
    }

    public function testGetWorkWithQuery()
    {
        $expectedWorkData = [
            'workId' => '84121955-b70f-47c2-ac02-f0171581680a',
            'workType' => 'MONOGRAPH',
            'workStatus' => 'ACTIVE',
            'fullTitle' => 'A ação mímica: princípios e poéticas da mímica corporal dramática de Étienne Decroux',
            'title' => 'A ação mímica',
            'subtitle' => 'princípios e poéticas da mímica corporal dramática de Étienne Decroux',
            'edition' => 1,
            'imprintId' => '5cf0b304-6ee5-45c7-a89d-53cd135d8d2b',
            'doi' => 'https://doi.org/10.7476/9786556303727',
            'publicationDate' => '2020-01-01',
            'pageCount' => 175,
            'license' => 'https://creativecommons.org/licenses/by/4.0/',
            'copyrightHolder' => null,
            'landingPage' => 'https://books.scielo.org/id/rzt8g',
            'longAbstract' => 'O que é mímica corporal dramática? Quais os seus princípios e fundamentos técnicos? ' .
                'Qual a sua aplicabilidade em projetos artísticos contemporâneos? ' .
                'Para discutir essas e outras questões, o livro apresenta alguns aspectos históricos, ' .
                'técnicos e poéticos dessa arte e aborda as controvertidas afirmações acerca das restrições de ' .
                'sua aplicação na contemporaneidade. A obra pretende fornecer um guia de estudos prático-teóricos ' .
                'para os interessados na Mímica Corporal Dramática de Étienne Decroux, como elemento complementar à ' .
                'prática nas salas de ensaio e no exercício cotidiano de artistas da cena.',
            'coverUrl' => 'https://books.scielo.org/id/rzt8g/cover/cover.jpeg',
            'contributions' => [
                [
                    'contributionId' => '5aa9d900-36fb-4ba8-83a5-ffd8db9a071a',
                    'contributorId' => '2a3c6871-8ef9-433b-8ac7-5e4c5f976c1f',
                    'workId' => '84121955-b70f-47c2-ac02-f0171581680a',
                    'contributionType' => 'AUTHOR',
                    'mainContribution' => true,
                    'biography' => null,
                    'firstName' => 'George',
                    'lastName' => 'Mascarenhas',
                    'fullName' => 'George Mascarenhas',
                    'contributionOrdinal' => 1
                ]
            ]
        ];
        $workData = $this->queryBuilder->get('84121955-b70f-47c2-ac02-f0171581680a');

        $this->assertEquals($expectedWorkData, $workData);
    }

    private function getProperties($object)
    {
        $reflector = new ReflectionClass($object);
        $properties = $reflector->getProperties(ReflectionProperty::IS_PRIVATE);

        $attributes = [];
        foreach ($properties as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($object);
            $attributes[$property->getName()] = $value;
        }

        return $attributes;
    }
}
