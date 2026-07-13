<?php

/**
 * @file plugins/generic/thoth/tests/classes/components/forms/FeatureVideoFormTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 */

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.components.forms.FeatureVideoForm');

class FeatureVideoFormTest extends PKPTestCase
{
    public function testProvidesOmpVideoUploadField(): void
    {
        $temporaryFilesUrl = 'https://example.test/api/v1/temporaryFiles';
        $form = new FeatureVideoForm(
            'https://example.test/api/v1/_submissions/1/featureVideo',
            $temporaryFilesUrl
        );

        $titleField = $form->getField('title');
        $videoField = $form->getField('video');

        $this->assertInstanceOf(\PKP\components\forms\FieldText::class, $titleField);
        $this->assertTrue($titleField->isRequired);
        $this->assertInstanceOf(\PKP\components\forms\FieldUpload::class, $videoField);
        $this->assertTrue($videoField->isRequired);
        $this->assertSame($temporaryFilesUrl, $videoField->options['url']);
        $this->assertSame('.mp4,.webm,.mov', $videoField->options['acceptedFiles']);
    }
}
