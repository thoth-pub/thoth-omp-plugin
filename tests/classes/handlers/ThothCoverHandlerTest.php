<?php

/**
 * @file plugins/generic/thoth/tests/classes/handlers/ThothCoverHandlerTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothCoverHandlerTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothCoverHandler
 *
 * @brief Test class for the ThothCoverHandler class
 */

require_once(__DIR__ . '/../../../vendor/autoload.php');

import('classes.publication.Publication');
import('classes.submission.Submission');
import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.handlers.ThothCoverHandler');

class ThothCoverHandlerTest extends PKPTestCase
{
    public function testUsesThothFrontcoverUrl()
    {
        $handler = new ThothCoverHandlerTestWrapper();
        $submission = $this->createSubmissionWithFrontcoverUrl('https://cdn.thoth.pub/frontcover.png');

        $this->assertSame('https://cdn.thoth.pub/frontcover.png', $handler->resolveThothFrontcoverUrl($submission));
    }

    public function testIgnoresInvalidThothFrontcoverUrl()
    {
        $handler = new ThothCoverHandlerTestWrapper();
        $submission = $this->createSubmissionWithFrontcoverUrl('javascript:alert(1)');

        $this->assertNull($handler->resolveThothFrontcoverUrl($submission));
    }

    private function createSubmissionWithFrontcoverUrl(string $frontcoverUrl)
    {
        $publication = $this->getMockBuilder(Publication::class)
            ->setMethods(['getData'])
            ->getMock();
        $publication->method('getData')
            ->with('thothFrontcoverUrl')
            ->willReturn($frontcoverUrl);

        $submission = $this->getMockBuilder(Submission::class)
            ->setMethods(['getCurrentPublication'])
            ->getMock();
        $submission->method('getCurrentPublication')->willReturn($publication);

        return $submission;
    }
}

class ThothCoverHandlerTestWrapper extends ThothCoverHandler
{
    public function resolveThothFrontcoverUrl($submission)
    {
        return $this->getThothFrontcoverUrl($submission);
    }
}
