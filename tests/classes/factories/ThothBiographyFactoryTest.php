<?php

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.factories.ThothBiographyFactory');

class ThothBiographyFactoryTest extends PKPTestCase
{
    public function testCreateFromAuthorWrapsBiographyWithoutParagraph()
    {
        $author = new class () {
            public function getData($key)
            {
                $values = [
                    'locale' => 'en_US',
                    'biography' => ['en_US' => 'English biography'],
                ];

                return $values[$key] ?? null;
            }
        };

        $factory = new ThothBiographyFactory();
        $thothBiographies = $factory->createFromAuthor($author, 'contribution-id', 'en_US');

        $this->assertSame('<p>English biography</p>', $thothBiographies['EN_US']->getContent());
    }

    public function testCreateFromAuthorPreservesBiographyAlreadyWrappedInParagraph()
    {
        $author = new class () {
            public function getData($key)
            {
                $values = [
                    'locale' => 'en_US',
                    'biography' => ['en_US' => '<p>English biography</p>'],
                ];

                return $values[$key] ?? null;
            }
        };

        $factory = new ThothBiographyFactory();
        $thothBiographies = $factory->createFromAuthor($author, 'contribution-id', 'en_US');

        $this->assertSame('<p>English biography</p>', $thothBiographies['EN_US']->getContent());
    }
}
