<?php

/**
 * @file plugins/generic/thoth/tests/classes/formatters/DoiFormatterTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DoiFormatterTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see DoiFormatter
 *
 * @brief Test class for the DoiFormatter class
 */

namespace APP\plugins\generic\thoth\tests\classes\formatters;

use APP\plugins\generic\thoth\classes\formatters\DoiFormatter;
use PKP\tests\PKPTestCase;

class DoiFormatterTest extends PKPTestCase
{
    public function testFormatDoiUrl()
    {
        $doi = '10.12345/11112222';

        $formattedDoi = DoiFormatter::resolveUrl($doi);

        $this->assertSame('https://doi.org/10.12345/11112222', $formattedDoi);
    }
}
