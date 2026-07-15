<?php

/**
 * @file plugins/generic/thoth/tests/classes/container/ContainerTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ContainerTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see Container
 *
 * @brief Test class for the Container class
 */

namespace APP\plugins\generic\thoth\tests\classes\container;

use APP\plugins\generic\thoth\classes\container\Container;
use APP\plugins\generic\thoth\classes\container\providers\ContainerProvider;
use Exception;
use PKP\tests\PKPTestCase;

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

    public function testSingletonReturnsSameInstance()
    {
        $container = new Container();

        $container->singleton('class', function ($container) {
            return new class () {
            };
        });

        $firstClass = $container->get('class');
        $secondClass = $container->get('class');

        $this->assertSame($firstClass, $secondClass);
    }

    public function testSetReplacesSingletonBinding()
    {
        $container = new Container();

        $container->singleton('foo', function () {
            return 'foo';
        });
        $container->get('foo');

        $container->set('foo', function () {
            return 'bar';
        });

        $this->assertSame('bar', $container->get('foo'));
    }

    public function testMakeResolvesDependencies()
    {
        $container = new Container();
        $container->set('foo', function () {
            return 'foo';
        });

        $class = $container->make(ContainerTestResolvedClass::class, [
            'foo',
            function () {
                return 'bar';
            },
            'baz',
        ]);

        $this->assertSame(['foo', 'bar', 'baz'], $class->dependencies);
    }

    public function testSingletonClassReturnsSameInstance()
    {
        $container = new Container();
        $container->set('foo', function () {
            return 'foo';
        });
        $container->singletonClass('class', ContainerTestResolvedClass::class, ['foo']);

        $firstClass = $container->get('class');
        $secondClass = $container->get('class');

        $this->assertSame($firstClass, $secondClass);
        $this->assertSame(['foo'], $firstClass->dependencies);
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

class ContainerTestResolvedClass
{
    public array $dependencies;

    public function __construct(...$dependencies)
    {
        $this->dependencies = $dependencies;
    }
}
