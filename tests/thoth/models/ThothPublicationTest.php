<?php

/**
 * @file plugins/generic/thoth/tests/thoth/models/ThothPublicationTest.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothPublicationTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothPublication
 *
 * @brief Test class for the ThothPublication class
 */

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.thoth.models.ThothPublication');

class ThothPublicationTest extends PKPTestCase
{
    public function testGettersAndSetters()
    {
        $publication = new ThothPublication();
        $publication->setId('8ac3e585-c32a-42d7-bd36-ef42ee397e6e');
        $publication->setWorkId('003137ea-4fe6-470d-8bd3-f936ad065f3c');
        $publication->setPublicationType(ThothPublication::PUBLICATION_TYPE_PAPERBACK);
        $publication->setIsbn('978-1-912656-00-4');

        $this->assertEquals('8ac3e585-c32a-42d7-bd36-ef42ee397e6e', $publication->getId());
        $this->assertEquals('003137ea-4fe6-470d-8bd3-f936ad065f3c', $publication->getWorkId());
        $this->assertEquals(ThothPublication::PUBLICATION_TYPE_PAPERBACK, $publication->getPublicationType());
        $this->assertEquals('978-1-912656-00-4', $publication->getIsbn());
    }

    public function testGetPublicationData()
    {
        $publication = new ThothPublication();
        $publication->setId('30ff947b-1bd9-4eef-8b91-753e9b12b935');
        $publication->setWorkId('006571ae-ac0e-4cb0-8a3f-71280aa7f23b');
        $publication->setPublicationType(ThothPublication::PUBLICATION_TYPE_PDF);
        $publication->setIsbn('978-0-615-94946-8');

        $this->assertEquals([
            'publicationId' => '30ff947b-1bd9-4eef-8b91-753e9b12b935',
            'workId' => '006571ae-ac0e-4cb0-8a3f-71280aa7f23b',
            'publicationType' => ThothPublication::PUBLICATION_TYPE_PDF,
            'isbn' => '978-0-615-94946-8',
        ], $publication->getData());
    }
}
