<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothChapterRepositoryTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothChapterRepositoryTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothChapterRepository
 *
 * @brief Test class for the ThothChapterRepository class
 */

namespace APP\plugins\generic\thoth\tests\classes\repositories;

use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Models\Work as ThothWork;
use APP\plugins\generic\thoth\classes\repositories\ThothChapterRepository;

class ThothChapterRepositoryTest extends PKPTestCase
{
    public function testGetChapterByDoi()
    {
        $expectedThothChapter = new ThothWork([
            'doi' => 'https://doi.org/10.12345/00001010'
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->onlyMethods(['chapterByDoi'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('chapterByDoi')
            ->willReturn($expectedThothChapter);

        $repository = new ThothChapterRepository($mockThothClient);

        $thothChapter = $repository->getByDoi('https://doi.org/10.12345/00001010');

        $this->assertEquals($expectedThothChapter, $thothChapter);
    }

    public function testFindChapter()
    {
        $expectedThothChapter = new ThothWork([
            'landingPage' => 'https://publisher.org/chapters/my_chapter'
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->onlyMethods(['chapters'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('chapters')
            ->willReturn([$expectedThothChapter]);

        $repository = new ThothChapterRepository($mockThothClient);

        $thothChapter = $repository->find('https://publisher.org/chapters/my_chapter');

        $this->assertEquals($expectedThothChapter, $thothChapter);
    }
}
