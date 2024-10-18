<?php

/**
 * @file plugins/generic/thoth/lib/thothAPI/exceptions/ThothException.inc.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Exception indicating a connection error with Thoth API.
 */

class ThothException extends \RuntimeException
{
    private $error;

    public function __construct($error, $code)
    {
        $this->error = $error;
        parent::__construct('Failed to send the request to Thoth: ' . $error, $code);
    }

    public function getError()
    {
        return $this->error;
    }
}
