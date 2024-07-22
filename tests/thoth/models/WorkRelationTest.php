<?php

/**
 * @file plugins/generic/thoth/tests/thoth/models/WorkRelationTest.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class WorkRelationTest
 * @ingroup plugins_generic_thoth_tests
 * @see WorkRelation
 *
 * @brief Test class for the WorkRelation class
 */

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.thoth.models.WorkRelation');

class WorkRelationTest extends PKPTestCase
{
    public function testGettersAndSetters()
    {
        $workRelationId = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $relatorWorkId = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $relatedWorkId = \Ramsey\Uuid\Uuid::uuid4()->toString();

        $relation = new WorkRelation();
        $relation->setId($workRelationId);
        $relation->setRelatorWorkId($relatorWorkId);
        $relation->setRelatedWorkId($relatedWorkId);
        $relation->setRelationType(WorkRelation::RELATION_TYPE_IS_CHILD_OF);
        $relation->setRelationOrdinal(1);

        $this->assertEquals($workRelationId, $relation->getId());
        $this->assertEquals($relatorWorkId, $relation->getRelatorWorkId());
        $this->assertEquals($relatedWorkId, $relation->getRelatedWorkId());
        $this->assertEquals(WorkRelation::RELATION_TYPE_IS_CHILD_OF, $relation->getRelationType());
        $this->assertEquals(1, $relation->getRelationOrdinal());
    }

    public function testGetRelationData()
    {
        $relation = new WorkRelation();
        $relation->setId('3e587b61-58f1-4064-bf80-e40e5c924d27');
        $relation->setRelatorWorkId('991f1070-67fa-4e6e-8519-114006043492');
        $relation->setRelatedWorkId('7d861db5-22f6-4ef8-abbb-b56ab8397624');
        $relation->setRelationType(WorkRelation::RELATION_TYPE_IS_CHILD_OF);
        $relation->setRelationOrdinal(1);

        $this->assertEquals([
            'workRelationId' => '3e587b61-58f1-4064-bf80-e40e5c924d27',
            'relatorWorkId' => '991f1070-67fa-4e6e-8519-114006043492',
            'relatedWorkId' => '7d861db5-22f6-4ef8-abbb-b56ab8397624',
            'relationType' => WorkRelation::RELATION_TYPE_IS_CHILD_OF,
            'relationOrdinal' => 1,
        ], $relation->getData());
    }
}
