<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothBookRegistrationResult.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothBookRegistrationResult
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Holds the result of a single Thoth book registration operation
 */

namespace APP\plugins\generic\thoth\classes\services;

class ThothBookRegistrationResult
{
    public function __construct(private string $workId, private array $warnings = [])
    {
    }

    public function getWorkId(): string
    {
        return $this->workId;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }
}
