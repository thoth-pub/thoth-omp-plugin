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
    private $warning;

    public function __construct($workId)
    {
        $this->workId = $workId;
    }

    public function getWorkId()
    {
        return $this->workId;
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
