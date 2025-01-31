<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothBookService.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothBookService
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth books
 */

class ThothBookService
{
    protected $factory;
    protected $repository;

    public function __construct($factory, $repository)
    {
        $this->factory = $factory;
        $this->repository = $repository;
    }

    public function register($submission, $request, $thothImprintId)
    {
        $thothBook = $this->factory->createFromSubmission($submission, $request);
        $thothBook->setImprintId($thothImprintId);

        $thothBookId = $this->repository->add($thothBook);

        return $thothBookId;
    }
}
