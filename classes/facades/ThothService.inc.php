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

import('plugins.generic.thoth.classes.container.ThothContainer');

class ThothService
{
    public static function affiliation()
    {
        return ThothContainer::getInstance()->get('affiliationService');
    }

    public static function book()
    {
        return ThothContainer::getInstance()->get('bookService');
    }

    public static function chapter()
    {
        return ThothContainer::getInstance()->get('chapterService');
    }

    public static function contribution()
    {
        return ThothContainer::getInstance()->get('contributionService');
    }

    public static function contributor()
    {
        return ThothContainer::getInstance()->get('contributorService');
    }

    public static function institution()
    {
        return ThothContainer::getInstance()->get('institutionService');
    }

    public static function language()
    {
        return ThothContainer::getInstance()->get('languageService');
    }

    public static function location()
    {
        return ThothContainer::getInstance()->get('locationService');
    }

    public static function publication()
    {
        return ThothContainer::getInstance()->get('publicationService');
    }

    public static function reference()
    {
        return ThothContainer::getInstance()->get('referenceService');
    }

    public static function subject()
    {
        return ThothContainer::getInstance()->get('subjectService');
    }

    public static function workRelation()
    {
        return ThothContainer::getInstance()->get('workRelationService');
    }
}
