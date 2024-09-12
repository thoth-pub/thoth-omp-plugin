<?php

/**
 * @file plugins/generic/thoth/tests/thoth/models/ThothReferenceTest.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothReferenceTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothReference
 *
 * @brief Test class for the ThothReference class
 */

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.lib.thothAPI.models.ThothReference');

class ThothReferenceTest extends PKPTestCase
{
    public function testGettersAndSetters()
    {
        $reference = new ThothReference();
        $reference->setId('5aebdd10-1b0c-40c0-8b10-372651b1eced');
        $reference->setWorkId('a8e11061-b281-42de-8638-c14f452e658e');
        $reference->setReferenceOrdinal(1);
        $reference->setUnstructuredCitation(
            'Stranack, K. (2018). Editorial: A New Path for Health Sciences. ' .
            'OJS3 Testdrive Journal, 1(3). https://doi.org/10.1234/td.v1i3.714'
        );

        $this->assertEquals('5aebdd10-1b0c-40c0-8b10-372651b1eced', $reference->getId());
        $this->assertEquals('a8e11061-b281-42de-8638-c14f452e658e', $reference->getWorkId());
        $this->assertEquals(1, $reference->getReferenceOrdinal());
        $this->assertEquals(
            'Stranack, K. (2018). Editorial: A New Path for Health Sciences. ' .
            'OJS3 Testdrive Journal, 1(3). https://doi.org/10.1234/td.v1i3.714',
            $reference->getUnstructuredCitation()
        );
    }

    public function testGetSubjectData()
    {
        $reference = new ThothReference();
        $reference->setId('62f3fb9b-2bce-4533-8862-da4c6a5b7b1c');
        $reference->setWorkId('9a6aab2b-8077-4cd3-9dd1-19c115f2a3ca');
        $reference->setReferenceOrdinal(4);
        $reference->setUnstructuredCitation(
            'Bezsheiko, V. (2021). Effectiveness of influenza vaccination ' .
            'for healthy adults: Versioning Example. OJS3 Testdrive Journal, 1(3). ' .
            'https://doi.org/10.1234/td.v1i3.722 (Original work published March 15, 2021)'
        );

        $this->assertEquals([
            'referenceId' => '62f3fb9b-2bce-4533-8862-da4c6a5b7b1c',
            'workId' => '9a6aab2b-8077-4cd3-9dd1-19c115f2a3ca',
            'referenceOrdinal' => 4,
            'unstructuredCitation' => 'Bezsheiko, V. (2021). Effectiveness of influenza vaccination ' .
                'for healthy adults: Versioning Example. OJS3 Testdrive Journal, 1(3). ' .
                'https://doi.org/10.1234/td.v1i3.722 (Original work published March 15, 2021)'
        ], $reference->getData());
    }
}
