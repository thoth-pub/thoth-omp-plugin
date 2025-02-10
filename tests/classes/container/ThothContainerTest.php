<?php

/**
 * @file plugins/generic/thoth/tests/classes/container/ThothContainerTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothContainerTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothContainer
 *
 * @brief Test class for the ThothContainer class
 */

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.container.ThothContainer');

class ThothContainerTest extends PKPTestCase
{
    public function testGetSameContainerInstance()
    {
        $firstContainer = ThothContainer::getInstance();
        $secondContainer = ThothContainer::getInstance();

        $this->assertInstanceOf(ThothContainer::class, $firstContainer);
        $this->assertSame($firstContainer, $secondContainer);
    }

    public function testReplaceContainerBinding()
    {
        ThothContainer::getInstance()->set('foo', function () {
            return 'foo';
        });

        $fooFoo = ThothContainer::getInstance()->get('foo');

        ThothContainer::getInstance()->set('foo', function () {
            return 'bar';
        });

        $fooBar = ThothContainer::getInstance()->get('foo');

        $this->assertEquals('foo', $fooFoo);
        $this->assertEquals('bar', $fooBar);
    }
}
