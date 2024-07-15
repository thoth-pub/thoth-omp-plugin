<?php

/**
 * @file plugins/generic/thoth/tests/thoth/models/WorkTest.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class WorkTest
 * @ingroup plugins_generic_thoth_tests
 * @see Work
 *
 * @brief Test class for the Work class
 */

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.thoth.models.Work');

class WorkTest extends PKPTestCase
{
    private $work;

    private $workData;

    public function setUp(): void
    {
        $this->workData['uuid'] = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $this->workData['workType'] = Work::WORK_TYPE_MONOGRAPH;
        $this->workData['workStatus'] = Work::WORK_STATUS_ACTIVE;
        $this->workData['fullTitle'] = 'Feliks Volkhovskii: A Revolutionary Life';
        $this->workData['title'] = 'Feliks Volkhovskii';
        $this->workData['subtitle'] = 'A Revolutionary Life';
        $this->workData['edition'] = 1;
        $this->workData['imprintId'] = 'e3cb2206-c2b6-4835-9f35-24bfa1572643';
        $this->workData['doi'] = 'https://doi.org/10.11647/OBP.0385';
        $this->workData['publicationDate'] = '2024-06-28';
        $this->workData['pageCount'] = 354;
        $this->workData['license'] = 'https://creativecommons.org/licenses/by-nc/4.0/';
        $this->workData['copyrightHolder'] = 'Michael Hughes';
        $this->workData['landingPage'] = 'https://www.openbookpublishers.com/books/10.11647/OBP.0385';
        $this->workData['longAbstract'] = 'Michael Hughes groundbreaking new biography provides a vivid history of ' .
            'this notable but hitherto neglected figure of both the political and literary worlds.';
        $this->workData['coverUrl'] = 'https://cdn.openbookpublishers.com/covers/10.11647/obp.0385.jpg';

        $this->work = new Work();
        $this->work->setId($this->workData['uuid']);
        $this->work->setWorkType($this->workData['workType']);
        $this->work->setWorkStatus($this->workData['workStatus']);
        $this->work->setFullTitle($this->workData['fullTitle']);
        $this->work->setTitle($this->workData['title']);
        $this->work->setSubtitle($this->workData['subtitle']);
        $this->work->setEdition($this->workData['edition']);
        $this->work->setImprintId($this->workData['imprintId']);
        $this->work->setDoi($this->workData['doi']);
        $this->work->setPublicationDate($this->workData['publicationDate']);
        $this->work->setPageCount($this->workData['pageCount']);
        $this->work->setLicense($this->workData['license']);
        $this->work->setCopyrightHolder($this->workData['copyrightHolder']);
        $this->work->setLandingPage($this->workData['landingPage']);
        $this->work->setLongAbstract($this->workData['longAbstract']);
        $this->work->setCoverUrl($this->workData['coverUrl']);
    }

    public function testGettersAndSetters()
    {
        $this->assertEquals($this->workData['uuid'], $this->work->getId());
        $this->assertEquals($this->workData['workType'], $this->work->getWorkType());
        $this->assertEquals($this->workData['workStatus'], $this->work->getWorkStatus());
        $this->assertEquals($this->workData['fullTitle'], $this->work->getFullTitle());
        $this->assertEquals($this->workData['title'], $this->work->getTitle());
        $this->assertEquals($this->workData['subtitle'], $this->work->getSubtitle());
        $this->assertEquals($this->workData['edition'], $this->work->getEdition());
        $this->assertEquals($this->workData['imprintId'], $this->work->getImprintId());
        $this->assertEquals($this->workData['doi'], $this->work->getDoi());
        $this->assertEquals($this->workData['publicationDate'], $this->work->getPublicationDate());
        $this->assertEquals($this->workData['pageCount'], $this->work->getPageCount());
        $this->assertEquals($this->workData['license'], $this->work->getLicense());
        $this->assertEquals($this->workData['copyrightHolder'], $this->work->getCopyrightHolder());
        $this->assertEquals($this->workData['landingPage'], $this->work->getLandingPage());
        $this->assertEquals($this->workData['longAbstract'], $this->work->getLongAbstract());
        $this->assertEquals($this->workData['coverUrl'], $this->work->getCoverUrl());
    }

    public function testGetWorkData()
    {
        $this->assertEquals(
            [
                'workId' => $this->workData['uuid'],
                'workType' => $this->workData['workType'],
                'workStatus' => $this->workData['workStatus'],
                'fullTitle' => $this->workData['fullTitle'],
                'title' => $this->workData['title'],
                'subtitle' => $this->workData['subtitle'],
                'edition' => $this->workData['edition'],
                'imprintId' => $this->workData['imprintId'],
                'doi' => $this->workData['doi'],
                'publicationDate' => $this->workData['publicationDate'],
                'pageCount' => $this->workData['pageCount'],
                'license' => $this->workData['license'],
                'copyrightHolder' => $this->workData['copyrightHolder'],
                'landingPage' => $this->workData['landingPage'],
                'longAbstract' => $this->workData['longAbstract'],
                'coverUrl' => $this->workData['coverUrl']
            ],
            $this->work->getData()
        );
    }

    public function testWorkTypeMapping()
    {
        $work = new Work();
        $this->assertEquals(Work::WORK_TYPE_EDITED_BOOK, $work->getSubmissionWorkType(WORK_TYPE_EDITED_VOLUME));
        $this->assertEquals(Work::WORK_TYPE_MONOGRAPH, $work->getSubmissionWorkType(WORK_TYPE_AUTHORED_WORK));
    }
}
