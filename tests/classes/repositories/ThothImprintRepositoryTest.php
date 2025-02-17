<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothImprintRepositoryTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothImprintRepositoryTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothImprintRepository
 *
 * @brief Test class for the ThothImprintRepository class
 */

use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Models\Imprint as ThothImprint;

import('plugins.generic.thoth.classes.repositories.ThothImprintRepository');

class ThothImprintRepositoryTest extends PKPTestCase
{
    public function testGetImprints()
    {
        $expectedThothImprints = [
            new ThothImprint([
                'imprintId' => 'a264c61f-c698-4c7c-b3d3-5d7988a4e348'
            ])
        ];

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['imprints'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('imprints')
            ->will($this->returnValue($expectedThothImprints));

        $thothPublisherIds = ['fffa1c59-4823-48ea-9d1c-596006a119b5'];

        $repository = new ThothImprintRepository($mockThothClient);
        $thothImprints = $repository->getMany($thothPublisherIds);

        $this->assertEquals($expectedThothImprints, $thothImprints);
    }
}
