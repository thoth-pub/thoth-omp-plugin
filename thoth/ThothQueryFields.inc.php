<?php

/**
 * @file plugins/generic/thoth/thoth/ThothQueryFields.inc.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothQueryFields
 * @ingroup plugins_generic_thoth
 *
 * @brief Retrieve object fields for queries
 */

import('plugins.generic.thoth.thoth.models.ThothAffiliation');
import('plugins.generic.thoth.thoth.models.ThothContribution');
import('plugins.generic.thoth.thoth.models.ThothContributor');
import('plugins.generic.thoth.thoth.models.ThothImprint');
import('plugins.generic.thoth.thoth.models.ThothInstitution');
import('plugins.generic.thoth.thoth.models.ThothLanguage');
import('plugins.generic.thoth.thoth.models.ThothLocation');
import('plugins.generic.thoth.thoth.models.ThothPublication');
import('plugins.generic.thoth.thoth.models.ThothPublisher');
import('plugins.generic.thoth.thoth.models.ThothReference');
import('plugins.generic.thoth.thoth.models.ThothSubject');
import('plugins.generic.thoth.thoth.models.ThothWork');
import('plugins.generic.thoth.thoth.models.ThothWorkRelation');

class ThothQueryFields
{
    public static function affiliation()
    {
        return self::getProperties(ThothAffiliation::class);
    }

    public static function contribution()
    {
        return self::getProperties(ThothContribution::class);
    }

    public static function contributor()
    {
        return self::getProperties(ThothContributor::class);
    }

    public static function imprint()
    {
        return self::getProperties(ThothImprint::class);
    }

    public static function institution()
    {
        return self::getProperties(ThothInstitution::class);
    }

    public static function language()
    {
        return self::getProperties(ThothLanguage::class);
    }

    public static function location()
    {
        return self::getProperties(ThothLocation::class);
    }

    public static function publication()
    {
        return self::getProperties(ThothPublication::class);
    }

    public static function publisher()
    {
        return self::getProperties(ThothPublisher::class);
    }

    public static function reference()
    {
        return self::getProperties(ThothReference::class);
    }

    public static function subject()
    {
        return self::getProperties(ThothSubject::class);
    }

    public static function work()
    {
        return self::getProperties(ThothWork::class);
    }

    public static function workRelation()
    {
        return self::getProperties(ThothWorkRelation::class);
    }

    private static function getProperties($object)
    {
        $reflector = new ReflectionClass($object);
        $properties = $reflector->getProperties(ReflectionProperty::IS_PRIVATE);

        return array_map(function ($prop) {
            return $prop->getName();
        }, $properties);
    }
}