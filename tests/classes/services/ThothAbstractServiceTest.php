<?php

use ThothApi\GraphQL\Client as ThothClient;

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.factories.ThothAbstractFactory');
import('plugins.generic.thoth.classes.repositories.ThothAbstractRepository');
import('plugins.generic.thoth.classes.services.ThothAbstractService');

class ThothAbstractServiceTest extends PKPTestCase
{
    public function testUpdateByPublication()
    {
        $mockRepository = $this->getMockBuilder(ThothAbstractRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->onlyMethods(['add', 'edit', 'delete'])
            ->getMock();
        $mockRepository->expects($this->once())->method('add');
        $mockRepository->expects($this->once())->method('edit');
        $mockRepository->expects($this->once())->method('delete')->with('removed-abstract-id');

        $publication = new class () {
            public function getData($key)
            {
                $values = [
                    'locale' => 'en_US',
                    'abstract' => [
                        'en_US' => '<p>English abstract</p>',
                        'pt_BR' => '<p>Resumo em portugues</p>',
                        'zz_ZZ' => '<p>Unsupported abstract</p>',
                    ],
                ];

                return $values[$key] ?? null;
            }
        };

        $service = new ThothAbstractService(new ThothAbstractFactory(), $mockRepository);
        $service->updateByPublication($publication, 'work-id', [
            ['abstractId' => 'existing-abstract-id', 'localeCode' => 'EN_US', 'abstractType' => 'LONG', 'canonical' => true],
            ['abstractId' => 'removed-abstract-id', 'localeCode' => 'ES', 'abstractType' => 'LONG', 'canonical' => false],
        ], 'en_US');
    }
}
