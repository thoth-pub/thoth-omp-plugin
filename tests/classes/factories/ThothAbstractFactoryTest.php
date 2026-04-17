<?php

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.factories.ThothAbstractFactory');

class ThothAbstractFactoryTest extends PKPTestCase
{
    public function testCreateFromPublicationWrapsAbstractWithoutParagraph()
    {
        $publication = new class () {
            public function getData($key)
            {
                $values = [
                    'locale' => 'en_US',
                    'abstract' => ['en_US' => 'English abstract'],
                ];

                return $values[$key] ?? null;
            }
        };

        $factory = new ThothAbstractFactory();
        $thothAbstracts = $factory->createFromPublication($publication, 'work-id', 'en_US');

        $this->assertSame('<p>English abstract</p>', $thothAbstracts['EN_US']->getContent());
    }

    public function testCreateFromPublicationPreservesAbstractAlreadyWrappedInParagraph()
    {
        $publication = new class () {
            public function getData($key)
            {
                $values = [
                    'locale' => 'en_US',
                    'abstract' => ['en_US' => '<p>English abstract</p>'],
                ];

                return $values[$key] ?? null;
            }
        };

        $factory = new ThothAbstractFactory();
        $thothAbstracts = $factory->createFromPublication($publication, 'work-id', 'en_US');

        $this->assertSame('<p>English abstract</p>', $thothAbstracts['EN_US']->getContent());
    }
}
