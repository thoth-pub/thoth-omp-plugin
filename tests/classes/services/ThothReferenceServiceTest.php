<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothReferenceServiceTest.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothReferenceServiceTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothReferenceService
 *
 * @brief Test class for the ThothReferenceService class
 */

use PKP\db\DAORegistry;
use PKP\tests\PKPTestCase;

import('plugins.generic.thoth.classes.services.ThothReferenceService');

class ThothReferenceServiceTest extends PKPTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->referenceService = new ThothReferenceService();
    }

    protected function tearDown(): void
    {
        unset($this->referenceService);
        parent::tearDown();
    }

    public function testCreateNewThothReference()
    {
        $citation = 'Bezsheiko, V. (2021). Effectiveness of influenza vaccination ' .
            'for healthy adults: Versioning Example. OJS3 Testdrive Journal, 1(3). ' .
            'https://doi.org/10.1234/td.v1i3.722 (Original work published March 15, 2021)';

        $expectedThothReference = new ThothReference();
        $expectedThothReference->setReferenceOrdinal(3);
        $expectedThothReference->setUnstructuredCitation($citation);

        $params = [
            'referenceOrdinal' => 3,
            'unstructuredCitation' => $citation
        ];

        $thothReference = $this->referenceService->new($params);

        $this->assertEquals($expectedThothReference, $thothReference);
    }

    public function testCreateNewThothReferenceByCitation()
    {
        $rawCitation = 'Fendrick AM, Monto AS, Nightengale B, Sarnes M. The economic burden of non-influenza-related ' .
            'viral respiratory tract infection in the United States. Arch Intern Med. 2003;163(4):487-494. ' .
            'DOI: https://doi.org/10.1001/archinte.163.4.487 PMID: https://www.ncbi.nlm.nih.gov/pubmed/12588210';

        $expectedThothReference = new ThothReference();
        $expectedThothReference->setReferenceOrdinal(1);
        $expectedThothReference->setUnstructuredCitation($rawCitation);

        $citation = DAORegistry::getDAO('CitationDAO')->_newDataObject();
        $citation->setRawCitation($rawCitation);
        $citation->setSequence(1);

        $thothReference = $this->referenceService->newByCitation($citation);

        $this->assertEquals($expectedThothReference, $thothReference);
    }

    public function testRegisterReference()
    {
        $workId = '9a6aab2b-8077-4cd3-9dd1-19c115f2a3ca';
        $rawCitation = 'Fendrick AM, Monto AS, Nightengale B, Sarnes M. The economic burden of non-influenza-related ' .
            'viral respiratory tract infection in the United States. Arch Intern Med. 2003;163(4):487-494. ' .
            'DOI: https://doi.org/10.1001/archinte.163.4.487 PMID: https://www.ncbi.nlm.nih.gov/pubmed/12588210';

        $expectedThothReference = new ThothReference();
        $expectedThothReference->setId('c9521541-6676-4cf4-ad6d-06299682718b');
        $expectedThothReference->setWorkId($workId);
        $expectedThothReference->setReferenceOrdinal(3);
        $expectedThothReference->setUnstructuredCitation($rawCitation);

        $citation = DAORegistry::getDAO('CitationDAO')->_newDataObject();
        $citation->setRawCitation($rawCitation);
        $citation->setSequence(3);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods([
                'createReference',
            ])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('createReference')
            ->will($this->returnValue('c9521541-6676-4cf4-ad6d-06299682718b'));

        $thothReference = $this->referenceService->register($mockThothClient, $citation, $workId);
        $this->assertEquals($expectedThothReference, $thothReference);
    }
}
