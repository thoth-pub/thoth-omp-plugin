<?php

/**
 * @file plugins/generic/thoth/classes/factories/ThothLocationFactory.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothLocationFactory
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief A factory to create Thoth locations
 */

namespace APP\plugins\generic\thoth\classes\factories;

use ThothApi\GraphQL\Enums\LocationPlatform;
use ThothApi\GraphQL\Inputs\PatchLocation as ThothLocation;

class ThothLocationFactory
{
    public function create(array $context): ThothLocation
    {
        $landingPage = $context['landingPage'];
        $fullTextUrl = $context['fullTextUrl'];

        $locationData = [
            'landingPage' => $landingPage,
            'locationPlatform' => LocationPlatform::OTHER,
        ];
        if ($fullTextUrl !== null && $fullTextUrl !== '') {
            $locationData['fullTextUrl'] = $fullTextUrl;
        }

        return new ThothLocation($locationData);
    }
}
