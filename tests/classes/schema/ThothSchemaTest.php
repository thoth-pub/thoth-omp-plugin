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

namespace APP\plugins\generic\thoth\tests\classes\schema;

use APP\plugins\generic\thoth\classes\schema\ThothSchema;
use PKP\tests\PKPTestCase;
use stdClass;

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

    public function testAddsFeatureVideoFieldsToPublicationSchema(): void
    {
        $schema = new stdClass();
        $schema->properties = new stdClass();
        $args = [&$schema];

        (new ThothSchema())->addToPublicationSchema('Schema::get::publication', $args);

        $this->assertSame('string', $schema->properties->thothFeatureVideoId->type);
        $this->assertSame('string', $schema->properties->thothFeatureVideoTitle->type);
        $this->assertSame('string', $schema->properties->thothFeatureVideoUrl->type);
        $this->assertSame('integer', $schema->properties->thothFeatureVideoWidth->type);
        $this->assertSame('integer', $schema->properties->thothFeatureVideoHeight->type);
        $this->assertSame('string', $schema->properties->thothFeatureVideoSha256->type);
    }
}
