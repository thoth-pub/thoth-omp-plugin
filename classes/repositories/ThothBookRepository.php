<?php

/**
 * @file plugins/generic/thoth/classes/repositories/ThothBookRepository.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothBookRepository
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief A repository to manage Thoth books
 */

namespace APP\plugins\generic\thoth\classes\repositories;

use ThothApi\Exception\QueryException;

class ThothBookRepository extends ThothWorkRepository
{
    public function getByDoi($doi)
    {
        try {
            return $this->thothClient->bookByDoi($doi);
        } catch (QueryException $e) {
            if ($this->isRecordNotFound($e)) {
                return null;
            }
            throw $e;
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
