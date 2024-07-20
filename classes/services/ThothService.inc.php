<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothService.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothService
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic Thoth API interactions
 */

import('plugins.generic.thoth.classes.services.WorkService');
import('plugins.generic.thoth.classes.services.ContributorService');
import('plugins.generic.thoth.classes.services.ContributionService');
import('plugins.generic.thoth.lib.APIKeyEncryption.APIKeyEncryption');
import('plugins.generic.thoth.thoth.ThothClient');

class ThothService
{
    private $plugin;

    private $contextId;

    public function __construct($plugin, $contextId)
    {
        $this->plugin = $plugin;
        $this->contextId = $contextId;
    }

    public function getThothClient()
    {
        $endpoint = $this->plugin->getSetting($this->contextId, 'apiUrl');
        $email = $this->plugin->getSetting($this->contextId, 'email');
        $password = $this->plugin->getSetting($this->contextId, 'password');

        if (!$email || !$password) {
            throw new Exception('Thoth credentials not configured.');
        }

        $password = APIKeyEncryption::decryptString($password);

        $client = new ThothClient($endpoint);
        $client->login($email, $password);

        return $client;
    }

    public function registerBook($submission)
    {
        $workService = new WorkService();
        $bookProps = $workService->getPropertiesBySubmission($submission);

        $book = $workService->new($bookProps);
        $book->setImprintId($this->plugin->getSetting($this->contextId, 'imprintId'));

        $bookId = $this->getThothClient()->createWork($book);
        $book->setId($bookId);

        $authors = DAORegistry::getDAO('AuthorDAO')->getByPublicationId($submission->getData('currentPublicationId'));
        foreach ($authors as $author) {
            $this->registerContribution($author, $bookId);
        }

        return $book;
    }

    public function registerContributor($author)
    {
        $contributorService = new contributorService();
        $contributorProps = $contributorService->getPropertiesByAuthor($author);

        $contributor = $contributorService->new($contributorProps);

        $contributorId = $this->getThothClient()->createContributor($contributor);
        $contributor->setId($contributorId);

        return $contributor;
    }

    public function registerContribution($author, $workId)
    {
        $contributionService = new ContributionService();
        $contributionProps = $contributionService->getPropertiesByAuthor($author);

        $contribution = $contributionService->new($contributionProps);
        $contribution->setWorkId($workId);

        $contributor = $this->registerContributor($author);
        $contribution->setContributorId($contributor->getId());

        $contributionId = $this->getThothClient()->createContribution($contribution);
        $contribution->setId($contributionId);

        return $contribution;
    }
}
