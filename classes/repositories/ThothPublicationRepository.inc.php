<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothPublicationRepository.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothPublicationRepository
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

    public function getIdByType($thothWorkId, $thothPublicationType)
    {
        $query = <<<GRAPHQL
        query(\$workId: Uuid!, \$publicationType: PublicationType!) {
            work(workId: \$workId) {
                publications(publicationTypes: [\$publicationType]) {
                    publicationId
                }
            }
        }
        GRAPHQL;

        $variables = [
            'workId' => $thothWorkId,
            'publicationType' => $thothPublicationType
        ];

        $result = $this->thothClient->rawQuery($query, $variables);
        $thothPublications = $result['work']['publications'];
        return !empty($thothPublications) ? $thothPublications[0]['publicationId'] : null;
    }

    public function find($filter)
    {
        $thothPublications =  $this->thothClient->publications([
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
