<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothBookRegistrationResult.inc.php
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

class ThothBookRegistrationResult
{
    private $workId;
    private $bookToActivate;
    private $warning;

    public function __construct($workId, $bookToActivate = null)
    {
        $this->workId = $workId;
        $this->bookToActivate = $bookToActivate;
    }

    public function getWorkId()
    {
        return $this->workId;
    }

    public function getBookToActivate()
    {
        return $this->bookToActivate;
    }

    public function shouldActivate()
    {
        return $this->bookToActivate !== null;
    }

    public function setWarning($warning)
    {
        $this->warning = $warning;
    }

    public function getWarning()
    {
        return $this->warning;
    }
}
