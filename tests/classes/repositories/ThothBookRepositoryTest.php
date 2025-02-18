<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothBookRepositoryTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothBookRepositoryTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothBookRepository
 *
 * @brief Test class for the ThothBookRepository class
 */

use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Models\Work as ThothWork;

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.repositories.ThothBookRepository');

class ThothBookRepositoryTest extends PKPTestCase
{
    public function testGetBookByDoi()
    {
        $expectedThothBook = new ThothWork([
            'doi' => 'https://doi.org/10.12345/10101010'
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['bookByDoi'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('bookByDoi')
            ->will($this->returnValue($expectedThothBook));

        $repository = new ThothBookRepository($mockThothClient);

        $thothBook = $repository->getByDoi('https://doi.org/10.12345/10101010');

        $this->assertEquals($expectedThothBook, $thothBook);
    }

    public function testFindBook()
    {
        $expectedThothBook = new ThothWork([
            'landingPage' => 'https://publisher.org/books/my_book'
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['books'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('books')
            ->will($this->returnValue([$expectedThothBook]));

        $repository = new ThothBookRepository($mockThothClient);

        $thothBook = $repository->find('https://publisher.org/books/my_book');

        $this->assertEquals($expectedThothBook, $thothBook);
    }
}
