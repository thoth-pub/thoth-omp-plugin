<?php

/**
 * @file plugins/generic/thoth/tests/classes/schema/ThothSchemaTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothSchemaTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothSchema
 *
 * @brief Test class for the ThothSchema class
 */

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.schema.ThothSchema');

class ThothSchemaTest extends PKPTestCase
{
    public function testAddFrontcoverSyncFieldsToPublicationSchema(): void
    {
        $schema = new stdClass();
        $schema->properties = new stdClass();

        $args = [&$schema];

        (new ThothSchema())->addToPublicationSchema('Schema::get::publication', $args);

        $this->assertSame('boolean', $schema->properties->thothUploadFrontcover->type);
        $this->assertSame('string', $schema->properties->thothFrontcoverSha256->type);
        $this->assertSame('string', $schema->properties->thothFrontcoverUrl->type);
    }

    public function testDoesNotAddFeatureVideoFieldsToPublicationSchema(): void
    {
        $schema = new stdClass();
        $schema->properties = new stdClass();
        $args = [&$schema];
        (new ThothSchema())->addToPublicationSchema('Schema::get::publication', $args);

        foreach (['Id', 'Title', 'Url', 'Width', 'Height', 'Sha256'] as $suffix) {
            $this->assertObjectNotHasProperty('thothFeatureVideo' . $suffix, $schema->properties);
        }
    }
}
