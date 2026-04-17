<?php

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
}
