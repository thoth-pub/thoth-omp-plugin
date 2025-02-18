<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothPublicationRepository.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothPublicationRepository
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief A repository to manage Thoth publications
 */

use ThothApi\GraphQL\Models\Publication as ThothPublication;

class ThothPublicationRepository
{
    protected $thothClient;

    public function __construct($thothClient)
    {
        $this->thothClient = $thothClient;
    }

    public function new(array $data = [])
    {
        return new ThothPublication($data);
    }

    public function get($thothPublicationId)
    {
        return $this->thothClient->publication($thothPublicationId);
    }

    public function find($filter)
    {
        $thothPublications = $this->thothClient->publications([
            'filter' => $filter,
            'limit' => 1
        ]);

        if (empty($thothPublications)) {
            return null;
        }

        return array_shift($thothPublications);
    }

    public function add($thothPublication)
    {
        return $this->thothClient->createPublication($thothPublication);
    }

    public function edit($thothPatchPublication)
    {
        return $this->thothClient->updatePublication($thothPatchPublication);
    }

    public function delete($thothPublicationId)
    {
        return $this->thothClient->deletePublication($thothPublicationId);
    }
}
