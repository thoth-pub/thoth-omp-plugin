<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothWorkLinkService.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothWorkLinkService
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Check whether a locally linked Work still exists in Thoth
 */

use ThothApi\Exception\QueryException;

class ThothWorkLinkService
{
    private const WORK_NOT_FOUND_MESSAGE = 'No record was found for the given ID';

    private $repository;

    public function __construct($repository)
    {
        $this->repository = $repository;
    }

    public function getStatus(string $thothWorkId): ?string
    {
        try {
            return $this->repository->get($thothWorkId)->getWorkStatus();
        } catch (QueryException $exception) {
            if (
                $exception->getStatusCode() === 200
                && rtrim($exception->getMessage(), '.') === self::WORK_NOT_FOUND_MESSAGE
            ) {
                return null;
            }

            throw $exception;
        }
    }
}
