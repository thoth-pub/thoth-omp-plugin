<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothMeService.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothMeService
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for the authenticated Thoth user
 */

namespace APP\plugins\generic\thoth\classes\services;

class ThothMeService
{
    public $repository;

    public function __construct($repository)
    {
        $this->repository = $repository;
    }

    public function getImprints()
    {
        $me = $this->repository->get();

        $imprints = [];
        foreach ($me->getPublisherContexts() as $publisherContext) {
            $publisher = $publisherContext->getPublisher();
            foreach ($publisher->getImprints() as $imprint) {
                $imprints[] = $imprint;
            }
        }

        return $imprints;
    }

    public function hasCdnWritePermission(): bool
    {
        $me = $this->repository->get();

        foreach ($me->getPublisherContexts() as $publisherContext) {
            $permissions = $publisherContext->getPermissions();
            if ($permissions && $permissions->getCdnWrite()) {
                return true;
            }
        }

        return false;
    }
}
