<?php

/**
 * @file plugins/generic/thoth/classes/exceptions/ThothRegistrationException.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothRegistrationException
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Preserves registration and compensation failures with the remote work identifier
 */

namespace APP\plugins\generic\thoth\classes\exceptions;

use RuntimeException;
use Throwable;

class ThothRegistrationException extends RuntimeException
{
    public function __construct(
        private string $workId,
        Throwable $failure,
        private Throwable $compensationFailure
    ) {
        parent::__construct('Thoth registration compensation failed for work ' . $workId, 0, $failure);
    }

    public function getWorkId(): string
    {
        return $this->workId;
    }

    public function getCompensationFailure(): Throwable
    {
        return $this->compensationFailure;
    }
}
