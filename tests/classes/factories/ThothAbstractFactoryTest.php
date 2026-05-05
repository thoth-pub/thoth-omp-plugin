<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.factories.ThothAbstractFactory');

class ThothAbstractFactoryTest extends PKPTestCase
{
    public function testCreateFromPublicationSendsAbstractWithoutParagraphUnchanged()
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

        $this->assertSame('English abstract', $thothAbstracts['EN_US']->getContent());
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

    public function testCreateFromPublicationMovesListsOutsideParagraphs()
    {
        $publication = new class () {
            public function getData($key)
            {
                $values = [
                    'locale' => 'en_US',
                    'abstract' => ['en_US' => 'Intro<ul><li>First item</li></ul>Outro'],
                ];

                return $values[$key] ?? null;
            }
        };

        $factory = new ThothAbstractFactory();
        $thothAbstracts = $factory->createFromPublication($publication, 'work-id', 'en_US');

        $this->assertSame(
            '<p>Intro</p><ul><li>First item</li></ul><p>Outro</p>',
            $thothAbstracts['EN_US']->getContent()
        );
    }

    public function testCreateFromPublicationMovesNestedListsOutsideParagraphs()
    {
        $publication = new class () {
            public function getData($key)
            {
                $values = [
                    'locale' => 'en_US',
                    'abstract' => ['en_US' => '<p>Intro<ul><li>First item</li></ul>Outro</p>'],
                ];

                return $values[$key] ?? null;
            }
        };

        $factory = new ThothAbstractFactory();
        $thothAbstracts = $factory->createFromPublication($publication, 'work-id', 'en_US');

        $this->assertSame(
            '<p>Intro</p><ul><li>First item</li></ul><p>Outro</p>',
            $thothAbstracts['EN_US']->getContent()
        );
    }

    public function testCreateFromPublicationConvertsBreaksToParagraphs()
    {
        $publication = new class () {
            public function getData($key)
            {
                $values = [
                    'locale' => 'en_US',
                    'abstract' => ['en_US' => '<p>First line<br />Second line</p>'],
                ];

                return $values[$key] ?? null;
            }
        };

        $factory = new ThothAbstractFactory();
        $thothAbstracts = $factory->createFromPublication($publication, 'work-id', 'en_US');

        $this->assertSame('<p>First line</p><p>Second line</p>', $thothAbstracts['EN_US']->getContent());
    }

    public function testCreateFromPublicationRemovesBreaksInsideInlineMarkup()
    {
        $publication = new class () {
            public function getData($key)
            {
                $values = [
                    'locale' => 'en_US',
                    'abstract' => ['en_US' => '<p><strong>First<br />Second</strong></p>'],
                ];

                return $values[$key] ?? null;
            }
        };

        $factory = new ThothAbstractFactory();
        $thothAbstracts = $factory->createFromPublication($publication, 'work-id', 'en_US');

        $this->assertSame('<p><strong>First Second</strong></p>', $thothAbstracts['EN_US']->getContent());
    }

    public function testCreateFromPublicationRemovesOmpPresentationWrapper()
    {
        $publication = new class () {
            public function getData($key)
            {
                $values = [
                    'locale' => 'en_US',
                    'abstract' => [
                        'en_US' => '<h2 class="label">Synopsis</h2><div class="value">'
                            . '<p>Publisher<br />Address<br />Country</p>'
                            . '<p><strong>Open</strong> <a href="https://example.com">platform</a></p>'
                            . '</div>',
                    ],
                ];

                return $values[$key] ?? null;
            }
        };

        $factory = new ThothAbstractFactory();
        $thothAbstracts = $factory->createFromPublication($publication, 'work-id', 'en_US');

        $this->assertSame(
            '<p>Publisher</p><p>Address</p><p>Country</p>'
                . '<p><strong>Open</strong> <a href="https://example.com">platform</a></p>',
            $thothAbstracts['EN_US']->getContent()
        );
    }
}
