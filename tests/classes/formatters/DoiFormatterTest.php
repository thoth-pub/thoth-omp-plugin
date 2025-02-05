<?php

/**
 * @file plugins/generic/thoth/tests/classes/formatters/DoiFormatterTest.php
 *
 * Copyright (c) 2025 Lepidus Tecnologia
 * Copyright (c) 2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DoiFormatterTest
 * @ingroup plugins_generic_thoth_tests
 * @see DoiFormatter
 *
 * @brief Test class for the DoiFormatter class
 */

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.formatters.DoiFormatter');

class DoiFormatterTest extends PKPTestCase
{
    public function testFormatDoiUrl()
    {
        $doi = '10.12345/11112222';

        $formattedDoi = DoiFormatter::resolveUrl($doi);

        $this->assertSame('https://doi.org/10.12345/11112222', $formattedDoi);
    }
}
