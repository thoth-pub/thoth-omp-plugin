<?php

/**
 * @file plugins/generic/thoth/classes/schema/ThothSchema.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothSchema
 * @ingroup plugins_generic_thoth
 *
 * @brief Class to add Thoth properties for storage in the database
 */

class ThothSchema
{
    public function addWorkIdToSchema($hookName, $args)
    {
        $schema = & $args[0];
        $schema->properties->{'thothWorkId'} = (object) [
            'type' => 'string',
            'apiSummary' => true,
            'validation' => ['nullable'],
        ];
        return false;
    }

    public function addPlaceToSchema($hookName, $args)
    {
        $schema = & $args[0];
        $schema->properties->{'place'} = (object) [
            'type' => 'string',
            'apiSummary' => true,
            'validation' => ['nullable'],
        ];
        return false;
    }

    public function addToBackendProps($hookName, $args)
    {
        $props = & $args[0];

        $props[] = 'thothWorkId';
    }
}
