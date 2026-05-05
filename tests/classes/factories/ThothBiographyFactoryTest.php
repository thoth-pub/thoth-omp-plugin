<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.factories.ThothBiographyFactory');

class ThothBiographyFactoryTest extends PKPTestCase
{
    public function testCreateFromAuthorSendsBiographyWithoutParagraphUnchanged()
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

        $this->assertSame('English biography', $thothBiographies['EN_US']->getContent());
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

    public function testCreateFromAuthorMovesListsOutsideParagraphs()
    {
        $author = new class () {
            public function getData($key)
            {
                $values = [
                    'locale' => 'en_US',
                    'biography' => ['en_US' => 'Intro<ul><li>First item</li></ul>Outro'],
                ];

                return $values[$key] ?? null;
            }
        };

        $factory = new ThothBiographyFactory();
        $thothBiographies = $factory->createFromAuthor($author, 'contribution-id', 'en_US');

        $this->assertSame(
            '<p>Intro</p><ul><li>First item</li></ul><p>Outro</p>',
            $thothBiographies['EN_US']->getContent()
        );
    }

    public function testCreateFromAuthorMovesNestedListsOutsideParagraphs()
    {
        $author = new class () {
            public function getData($key)
            {
                $values = [
                    'locale' => 'en_US',
                    'biography' => ['en_US' => '<p>Intro<ul><li>First item</li></ul>Outro</p>'],
                ];

                return $values[$key] ?? null;
            }
        };

        $factory = new ThothBiographyFactory();
        $thothBiographies = $factory->createFromAuthor($author, 'contribution-id', 'en_US');

        $this->assertSame(
            '<p>Intro</p><ul><li>First item</li></ul><p>Outro</p>',
            $thothBiographies['EN_US']->getContent()
        );
    }

    public function testCreateFromAuthorConvertsBreaksToParagraphs()
    {
        $author = new class () {
            public function getData($key)
            {
                $values = [
                    'locale' => 'en_US',
                    'biography' => ['en_US' => '<p>First line<br />Second line</p>'],
                ];

                return $values[$key] ?? null;
            }
        };

        $factory = new ThothBiographyFactory();
        $thothBiographies = $factory->createFromAuthor($author, 'contribution-id', 'en_US');

        $this->assertSame('<p>First line</p><p>Second line</p>', $thothBiographies['EN_US']->getContent());
    }

    public function testCreateFromAuthorRemovesBreaksInsideInlineMarkup()
    {
        $author = new class () {
            public function getData($key)
            {
                $values = [
                    'locale' => 'en_US',
                    'biography' => ['en_US' => '<p><strong>First<br />Second</strong></p>'],
                ];

                return $values[$key] ?? null;
            }
        };

        $factory = new ThothBiographyFactory();
        $thothBiographies = $factory->createFromAuthor($author, 'contribution-id', 'en_US');

        $this->assertSame('<p><strong>First Second</strong></p>', $thothBiographies['EN_US']->getContent());
    }

    public function testCreateFromAuthorRemovesOmpPresentationWrapper()
    {
        $author = new class () {
            public function getData($key)
            {
                $values = [
                    'locale' => 'en_US',
                    'biography' => [
                        'en_US' => '<h2 class="label">Biography</h2><div class="value">'
                            . '<p>Institution<br />Department</p>'
                            . '<p><strong>Research</strong> <a href="https://example.com">profile</a></p>'
                            . '</div>',
                    ],
                ];

                return $values[$key] ?? null;
            }
        };

        $factory = new ThothBiographyFactory();
        $thothBiographies = $factory->createFromAuthor($author, 'contribution-id', 'en_US');

        $this->assertSame(
            '<p>Institution</p><p>Department</p>'
                . '<p><strong>Research</strong> <a href="https://example.com">profile</a></p>',
            $thothBiographies['EN_US']->getContent()
        );
    }
}
