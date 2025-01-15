<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothContributionService.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothContributionService
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth contributions
 */

import('plugins.generic.thoth.classes.facades.ThothService');
import('plugins.generic.thoth.lib.thothAPI.models.ThothContribution');
import('classes.core.Services');

class ThothContributionService
{
    public function new($params)
    {
        $contribution = new ThothContribution();
        $contribution->setId($params['contributionId'] ?? null);
        $contribution->setWorkId($params['workId'] ?? null);
        $contribution->setContributorId($params['contributorId'] ?? null);
        $contribution->setContributionType($params['contributionType']);
        $contribution->setMainContribution($params['mainContribution']);
        $contribution->setContributionOrdinal($params['contributionOrdinal']);
        $contribution->setLastName($params['lastName']);
        $contribution->setFullName($params['fullName']);
        $contribution->setFirstName($params['firstName'] ?? null);
        $contribution->setBiography($params['biography'] ?? null);
        return $contribution;
    }

    public function newByAuthor($author)
    {
        return $this->new($this->getDataByAuthor($author));
    }

    public function getDataByAuthor($author)
    {
        $userGroupLocaleKey = $author->getUserGroup()->getData('nameLocaleKey');

        $allowedTags = '<b><strong><em><i><u><ul><ol><li><p><h1><h2><h3><h4><h5><h6>';

        $data = [];
        $data['contributionType'] = $this->getContributionTypeByUserGroupLocaleKey($userGroupLocaleKey);
        $data['mainContribution'] = $this->isMainContribution($author);
        $data['contributionOrdinal'] = $author->getSequence() + 1;
        $data['firstName'] = $author->getLocalizedGivenName();
        $data['lastName'] = $author->getLocalizedData('familyName');
        $data['fullName'] = $author->getFullName(false);
        $data['biography'] = strip_tags($author->getLocalizedBiography(), $allowedTags);
        return $data;
    }

    public function register($thothClient, $author, $thothWorkId)
    {
        $contribution = $this->newByAuthor($author);
        $contribution->setWorkId($thothWorkId);

        if (!$contribution->getContributionType()) {
            return;
        }

        $contributors = ThothService::contributor()->getMany($thothClient, [
            'limit' => 1,
            'filter' => (!empty($author->getOrcid())) ?
                $author->getOrcid() :
                $author->getFullName(false)
        ]);

        $contributor = empty($contributors) ?
            ThothService::contributor()->register($thothClient, $author) :
            array_shift($contributors);

        $contribution->setContributorId($contributor->getId());

        $contributionId = $thothClient->createContribution($contribution);
        $contribution->setId($contributionId);

        if ($affiliation = $author->getLocalizedAffiliation()) {
            ThothService::affiliation()->register($thothClient, $affiliation, $contributionId);
        }

        return $contribution;
    }

    public function updateContributions($thothClient, $thothContributions, $publication, $thothWorkId)
    {
        $authors = DAORegistry::getDAO('AuthorDAO')->getByPublicationId($publication->getId());

        $publicationContributions = array_map(function ($author) {
            return $this->getDataByAuthor($author);
        }, $authors);
        foreach ($thothContributions as $thothContribution) {
            if (!$this->contributionInList($thothContribution, $publicationContributions)) {
                $thothClient->deleteContribution($thothContribution['contributionId']);
            }
        }

        foreach ($authors as $author) {
            $publicationContribution = $this->getDataByAuthor($author);
            if (!$thothContribution = $this->contributionInList($publicationContribution, $thothContributions)) {
                $this->register($thothClient, $author, $thothWorkId);
                continue;
            }
            if ($thothContribution['biography'] !== $publicationContribution['biography']) {
                $thothContribution['biography'] = $publicationContribution['biography'];
                $thothClient->updateContribution($this->new($thothContribution));
            }
        }
    }

    private function contributionInList($targetContribution, $contributions)
    {
        foreach ($contributions as $contribution) {
            if (
                $contribution['firstName'] === $targetContribution['firstName']
                && $contribution['lastName'] === $targetContribution['lastName']
            ) {
                return $contribution;
            }
        }
        return null;
    }

    private function isMainContribution($author)
    {
        if ($author instanceof ChapterAuthor) {
            return (bool) $author->getPrimaryContact();
        }

        $publication = Services::get('publication')->get($author->getData('publicationId'));
        return $publication->getData('primaryContactId') == $author->getId();
    }

    public function getContributionTypeByUserGroupLocaleKey($userGroupLocaleKey)
    {
        $contributionTypeMapping = [
            'default.groups.name.author' => ThothContribution::CONTRIBUTION_TYPE_AUTHOR,
            'default.groups.name.chapterAuthor' => ThothContribution::CONTRIBUTION_TYPE_AUTHOR,
            'default.groups.name.volumeEditor' => ThothContribution::CONTRIBUTION_TYPE_EDITOR,
            'default.groups.name.translator' => ThothContribution::CONTRIBUTION_TYPE_TRANSLATOR,
        ];
        return $contributionTypeMapping[$userGroupLocaleKey];
    }
}
