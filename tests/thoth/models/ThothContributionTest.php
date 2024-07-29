<?php

/**
 * @file plugins/generic/thoth/tests/thoth/models/ThothContributionTest.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothContributionTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothContribution
 *
 * @brief Test class for the ThothContribution class
 */

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.thoth.models.ThothContribution');

class ThothContributionTest extends PKPTestCase
{
    public function testGettersAndSetters()
    {
        $uuid = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $contribution = new ThothContribution();
        $contribution->setId($uuid);
        $contribution->setWorkId('b5c810e1-c847-4553-a24e-9893164d9786');
        $contribution->setContributorId('454d55ec-6c4c-42b9-bbf9-fa08b70d7f1d');
        $contribution->setContributionType(ThothContribution::CONTRIBUTION_TYPE_AUTHOR);
        $contribution->setMainContribution(true);
        $contribution->setContributionOrdinal(1);
        $contribution->setFirstName('Anthony');
        $contribution->setLastName('Williams');
        $contribution->setFullName('Anthony Williams');
        $contribution->setBiography(
            'Anthony Williams is Director of External Communications at the European Bank for ' .
            'Reconstruction and Development (EBRD).'
        );

        $this->assertEquals($uuid, $contribution->getId());
        $this->assertEquals('b5c810e1-c847-4553-a24e-9893164d9786', $contribution->getWorkId());
        $this->assertEquals('454d55ec-6c4c-42b9-bbf9-fa08b70d7f1d', $contribution->getContributorId());
        $this->assertEquals(ThothContribution::CONTRIBUTION_TYPE_AUTHOR, $contribution->getContributionType());
        $this->assertEquals(1, $contribution->getContributionOrdinal());
        $this->assertEquals('Anthony', $contribution->getFirstName());
        $this->assertEquals('Williams', $contribution->getLastName());
        $this->assertEquals('Anthony Williams', $contribution->getFullName());
        $this->assertEquals(
            'Anthony Williams is Director of External Communications at the European Bank for ' .
            'Reconstruction and Development (EBRD).',
            $contribution->getBiography()
        );
    }

    public function testGetContributionData()
    {
        $uuid = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $contribution = new ThothContribution();
        $contribution->setId($uuid);
        $contribution->setWorkId('e763a10c-1e2b-4b10-84c4-ac3f95236a97');
        $contribution->setContributorId('e1de541c-e84b-4092-941f-dab9b5dac865');
        $contribution->setContributionType(ThothContribution::CONTRIBUTION_TYPE_EDITOR);
        $contribution->setMainContribution(false);
        $contribution->setContributionOrdinal(1);
        $contribution->setFirstName('Thomas');
        $contribution->setLastName('Pringle');
        $contribution->setFullName('Thomas Patrick Pringle');
        $contribution->setBiography(
            'Thomas Pringle is an SSHRC doctoral and presidential fellow at Brown University, ' .
            'where he is a PhD candidate in the Department of Modern Culture and Media.'
        );

        $this->assertEquals([
            'contributionId' => $uuid,
            'workId' => 'e763a10c-1e2b-4b10-84c4-ac3f95236a97',
            'contributorId' => 'e1de541c-e84b-4092-941f-dab9b5dac865',
            'contributionType' => ThothContribution::CONTRIBUTION_TYPE_EDITOR,
            'mainContribution' => false,
            'contributionOrdinal' => 1,
            'firstName' => 'Thomas',
            'lastName' => 'Pringle',
            'fullName' => 'Thomas Patrick Pringle',
            'biography' => 'Thomas Pringle is an SSHRC doctoral and presidential fellow at Brown University, ' .
                'where he is a PhD candidate in the Department of Modern Culture and Media.'
        ], $contribution->getData());
    }
}
