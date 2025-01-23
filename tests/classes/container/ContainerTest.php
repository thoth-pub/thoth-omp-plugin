<?php

/**
 * @file plugins/generic/thoth/tests/classes/container/ContainerTest.php
 *
 * Copyright (c) 2025 Lepidus Tecnologia
 * Copyright (c) 2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ContainerTest
 * @ingroup plugins_generic_thoth_tests
 * @see Container
 *
 * @brief Test class for the Container class
 */

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.container.ContainerProvider');
import('plugins.generic.thoth.classes.container.Container');

class ContainerTest extends PKPTestCase
{
    public function testContainerReturnInstance()
    {
        $container = new Container();

        $container->set('class', function ($container) {
            return new class () {
                public function foo()
                {
                    return 'foo';
                }
            };
        });

        $class = $container->get('class');

        $this->assertEquals('foo', $class->foo());
    }

    public function testBackupContainerBinding()
    {
        $callable = function () {
            return 'foo';
        };

        $container = new Container();
        $container->set('foo', $callable);

        $fooBackup = $container->backup('foo');

        $this->assertSame($callable, $fooBackup);
    }

    public function testInvalidBindingThrownException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Target binding "class" does not exist.');

        $container = new Container();
        $container->get('class');
    }

    public function testRegisterInstancesFromProvider()
    {
        $provider = new class () implements ContainerProvider {
            public function register($container)
            {
                $container->set('class', function ($container) {
                    return new class () {
                        public function foo()
                        {
                            return 'foo';
                        }
                    };
                });
            }
        };

        $container = new Container();
        $container->register($provider);

        $class = $container->get('class');

        $this->assertEquals('foo', $class->foo());
    }
}
