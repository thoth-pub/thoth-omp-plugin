<?php

/**
 * @file plugins/generic/thoth/classes/exceptions/MetadataSynchronizationException.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class MetadataSynchronizationException
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Indicates that metadata cannot be synchronized without editorial review
 */

namespace APP\plugins\generic\thoth\classes\exceptions;

class MetadataSynchronizationException extends \UnexpectedValueException
{
}
