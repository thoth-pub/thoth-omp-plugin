<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothImprintRepository.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothImprintRepository
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief A repository to manage Thoth imprints
 */

namespace APP\plugins\generic\thoth\classes\repositories;

class ThothImprintRepository
{
    protected $thothClient;

    public function __construct($thothClient)
    {
        $this->thothClient = $thothClient;
    }

    public function getMany($thothPublisherIds = [])
    {
        $args['publishers'] = $thothPublisherIds;

        return $this->thothClient->imprints($args);
    }
}
