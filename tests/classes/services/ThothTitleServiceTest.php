<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');
use ThothApi\GraphQL\Client as ThothClient;

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.factories.ThothTitleFactory');
import('plugins.generic.thoth.classes.repositories.ThothTitleRepository');
import('plugins.generic.thoth.classes.services.ThothTitleService');

class ThothTitleServiceTest extends PKPTestCase
{
    public function testUpdateByPublication()
    {
        $mockRepository = $this->getMockBuilder(ThothTitleRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->onlyMethods(['add', 'edit', 'delete'])
            ->getMock();
        $mockRepository->expects($this->once())->method('add');
        $mockRepository->expects($this->once())->method('edit');
        $mockRepository->expects($this->once())->method('delete')->with('removed-title-id');

        $publication = new class () {
            public function getData($key)
            {
                $values = [
                    'locale' => 'en_US',
                    'title' => [
                        'en_US' => 'English title',
                        'pt_BR' => 'Titulo em portugues',
                        'zz_ZZ' => 'Unsupported title',
                    ],
                    'subtitle' => [
                        'en_US' => 'English subtitle',
                        'pt_BR' => 'Subtitulo em portugues',
                        'zz_ZZ' => 'Unsupported subtitle',
                    ],
                ];

                return $values[$key] ?? null;
            }
        };

        $service = new ThothTitleService(new ThothTitleFactory(), $mockRepository);
        $service->updateByPublication($publication, 'work-id', [
            ['titleId' => 'existing-title-id', 'localeCode' => 'EN_US', 'canonical' => true],
            ['titleId' => 'removed-title-id', 'localeCode' => 'ES', 'canonical' => false],
        ], 'en_US');
    }

    public function testDemotesExistingCanonicalTitleBeforePromotingAnotherTitle()
    {
        $editedTitles = [];
        $mockRepository = $this->getMockBuilder(ThothTitleRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->onlyMethods(['add', 'edit', 'delete'])
            ->getMock();
        $mockRepository->expects($this->never())->method('add');
        $mockRepository->expects($this->exactly(2))
            ->method('edit')
            ->willReturnCallback(function ($title) use (&$editedTitles) {
                $editedTitles[] = [
                    'titleId' => $title->getTitleId(),
                    'canonical' => $title->getCanonical(),
                ];
            });
        $mockRepository->expects($this->never())->method('delete');

        $publication = new class () {
            public function getData($key)
            {
                $values = [
                    'locale' => 'pt_BR',
                    'title' => [
                        'pt_BR' => 'Titulo em portugues',
                        'en_US' => 'English title',
                    ],
                    'subtitle' => [],
                ];

                return $values[$key] ?? null;
            }
        };

        $service = new ThothTitleService(new ThothTitleFactory(), $mockRepository);
        $service->updateByPublication($publication, 'work-id', [
            ['titleId' => 'en-title-id', 'localeCode' => 'EN_US', 'canonical' => true],
            ['titleId' => 'pt-title-id', 'localeCode' => 'PT_BR', 'canonical' => false],
        ], 'pt_BR');

        $this->assertSame([
            ['titleId' => 'en-title-id', 'canonical' => false],
            ['titleId' => 'pt-title-id', 'canonical' => true],
        ], $editedTitles);
    }
}
