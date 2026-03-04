<?php

/**
 * @file plugins/generic/thoth/tests/classes/formatters/HtmlStripperTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class HtmlStripperTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see HtmlStripper
 *
 * @brief Test class for the HtmlStripper class
 */

namespace APP\plugins\generic\thoth\tests\classes\formatters;

use PKP\tests\PKPTestCase;
use APP\plugins\generic\thoth\classes\formatters\HtmlStripper;

class HtmlStripperTest extends PKPTestCase
{
    public function testStripHtmlTags()
    {
        $string = '<p>This is a <a href="#">string</a> with <strong>html tags</strong></p>';

        $strippedString = HtmlStripper::stripTags($string);

        $this->assertSame('This is a string with <strong>html tags</strong>', $strippedString);
    }
}
