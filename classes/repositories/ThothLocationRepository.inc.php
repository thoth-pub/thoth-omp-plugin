<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothLocationRepository.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothLocationRepository
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief A repository to manage Thoth locations
 */

use ThothApi\GraphQL\Models\Location as ThothLocation;

class ThothLocationRepository
{
    protected $thothClient;

    public function __construct($thothClient)
    {
        $this->thothClient = $thothClient;
    }

    public function new(array $data = [])
    {
        return new ThothLocation($data);
    }

    public function get($thothLocationId)
    {
        return $this->thothClient->location($thothLocationId);
    }

    public function hasCanonical($thothPublicationId)
    {
        $query = <<<GQL
        query(\$publicationId: Uuid!) {
            publication(publicationId: \$publicationId) {
                locations {
                    canonical
                }
            }
        }
        GQL;

        $result = $this->thothClient->rawQuery($query, ['publicationId' => $thothPublicationId]);
        $locations = $result['publication']['locations'];
        $hasCanonical = array_search(true, $locations);

        return $hasCanonical !== false;
    }

    public function add($thothLocation)
    {
        return $this->thothClient->createLocation($thothLocation);
    }

    public function edit($thothPatchLocation)
    {
        return $this->thothClient->updateLocation($thothPatchLocation);
    }

    public function delete($thothLocationId)
    {
        return $this->thothClient->deleteLocation($thothLocationId);
    }
}
