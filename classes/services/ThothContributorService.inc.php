<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothContributorService.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothContributorService
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth contributors
 */

use ThothApi\GraphQL\Models\Contributor as ThothContributor;

class ThothContributorService
{
    public $factory;
    public $repository;

    public function __construct($factory, $repository)
    {
        $this->factory = $factory;
        $this->repository = $repository;
    }

    public function register($author)
    {
        $thothContributor = $this->factory->createFromAuthor($author);
        return $this->repository->add($thothContributor);
    }
}
