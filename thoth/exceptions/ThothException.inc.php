<?php

/**
 * @file plugins/generic/thoth/thoth/exceptions/ThothException.inc.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Exception indicating a connection error with Thoth API.
 */

class ThothException extends \RuntimeException
{
    public function __construct($error, $code)
    {
        parent::__construct('Failed to send the request to Thoth: ' . $error, $code);
    }
}
