<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothBookRepository.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothBookRepository
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief A repository to manage Thoth books
 */

use ThothApi\Exception\QueryException;

import('plugins.generic.thoth.classes.repositories.ThothWorkRepository');

class ThothBookRepository extends ThothWorkRepository
{
    public function getByDoi($doi)
    {
        try {
            return $this->thothClient->bookByDoi($doi);
        } catch (QueryException $e) {
            return null;
        }
    }

    public function find($filter)
    {
        $thothBooks = $this->thothClient->books([
            'filter' => $filter,
            'limit' => 1
        ]);

        if (empty($thothBooks)) {
            return null;
        }

        return array_shift($thothBooks);
    }
}
