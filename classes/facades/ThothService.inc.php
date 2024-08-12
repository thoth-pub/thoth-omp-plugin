<?php

/**
 * @file plugins/generic/thoth/classes/facades/ThothService.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothService
 *
 * @brief This facade provides access to all Services for Thoth.
 */

import('plugins.generic.thoth.classes.services.ThothAffiliationService');
import('plugins.generic.thoth.classes.services.ThothContributionService');
import('plugins.generic.thoth.classes.services.ThothContributorService');
import('plugins.generic.thoth.classes.services.ThothInstitutionService');
import('plugins.generic.thoth.classes.services.ThothLanguageService');
import('plugins.generic.thoth.classes.services.ThothLocationService');
import('plugins.generic.thoth.classes.services.ThothPublicationService');
import('plugins.generic.thoth.classes.services.ThothReferenceService');
import('plugins.generic.thoth.classes.services.ThothSubjectService');
import('plugins.generic.thoth.classes.services.ThothWorkService');

class ThothService
{
    public static function affiliation()
    {
        return app(ThothAffiliationService::class);
    }

    public static function contribution()
    {
        return app(ThothContributionService::class);
    }

    public static function contributor()
    {
        return app(ThothContributorService::class);
    }

    public static function institution()
    {
        return app(ThothInstitutionService::class);
    }

    public static function language()
    {
        return app(ThothLanguageService::class);
    }

    public static function location()
    {
        return app(ThothLocationService::class);
    }

    public static function publication()
    {
        return app(ThothPublicationService::class);
    }

    public static function reference()
    {
        return app(ThothReferenceService::class);
    }

    public static function subject()
    {
        return app(ThothSubjectService::class);
    }

    public static function work()
    {
        return app(ThothWorkService::class);
    }
}
