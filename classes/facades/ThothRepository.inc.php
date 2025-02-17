<?php

/**
 * @file plugins/generic/thoth/classes/facades/ThothRepository.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothRepository
 *
 * @brief This facade provides access to all repositories for Thoth.
 */

import('plugins.generic.thoth.classes.container.ThothContainer');

class ThothRepository
{
    public static function account()
    {
        return ThothContainer::getInstance()->get('accountRepository');
    }

    public static function affiliation()
    {
        return ThothContainer::getInstance()->get('affiliationRepository');
    }

    public static function contribution()
    {
        return ThothContainer::getInstance()->get('contributionRepository');
    }

    public static function contributor()
    {
        return ThothContainer::getInstance()->get('contributorRepository');
    }

    public static function imprint()
    {
        return ThothContainer::getInstance()->get('imprintRepository');
    }

    public static function institution()
    {
        return ThothContainer::getInstance()->get('institutionRepository');
    }

    public static function language()
    {
        return ThothContainer::getInstance()->get('languageRepository');
    }

    public static function location()
    {
        return ThothContainer::getInstance()->get('locationRepository');
    }

    public static function publication()
    {
        return ThothContainer::getInstance()->get('publicationRepository');
    }

    public static function reference()
    {
        return ThothContainer::getInstance()->get('referenceRepository');
    }

    public static function subject()
    {
        return ThothContainer::getInstance()->get('subjectRepository');
    }

    public static function workRelation()
    {
        return ThothContainer::getInstance()->get('workRelationRepository');
    }

    public static function work()
    {
        return ThothContainer::getInstance()->get('workRepository');
    }
}
