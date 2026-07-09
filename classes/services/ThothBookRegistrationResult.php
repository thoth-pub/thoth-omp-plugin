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
    private string $workId;
    private $bookToActivate;

    public function __construct(string $workId, $bookToActivate = null)
    {
        $this->workId = $workId;
        $this->bookToActivate = $bookToActivate;
    }

    public function getWorkId(): string
    {
        return $this->workId;
    }

    public function getBookToActivate()
    {
        return $this->bookToActivate;
    }

    public function shouldActivate(): bool
    {
        return $this->bookToActivate !== null;
    }
}
