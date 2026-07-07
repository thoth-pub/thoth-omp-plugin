<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothBookRegistrationService.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothBookRegistrationService
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Coordinates full Thoth book registration workflows
 */

namespace APP\plugins\generic\thoth\classes\services;

use APP\plugins\generic\thoth\classes\facades\ThothService;
use ThothApi\GraphQL\Enums\WorkStatus;

class ThothBookRegistrationService
{
    private $factory;
    private $repository;
    private $originalThothBook;
    private $registeredEntryId;

    public function __construct($factory, $repository)
    {
        $this->factory = $factory;
        $this->repository = $repository;
    }

    public function register($publication, $thothImprintId)
    {
        $thothBook = $this->factory->createFromPublication($publication);
        $thothBook->setImprintId($thothImprintId);

        if ($thothBook->getWorkStatus() === WorkStatus::ACTIVE) {
            $this->originalThothBook = clone $thothBook;
            $thothBook->setWorkStatus(WorkStatus::FORTHCOMING);
        }

        $thothBookId = $this->repository->add($thothBook);
        $publication->setData('thothBookId', $thothBookId);
        $this->registeredEntryId = $thothBookId;
        $this->registerMetadata($publication, $thothBookId);

        ThothService::contribution()->registerByPublication($publication);
        ThothService::publication()->registerByPublication($publication);
        ThothService::language()->registerByPublication($publication);
        ThothService::subject()->registerByPublication($publication);
        ThothService::reference()->registerByPublication($publication);
        ThothService::workRelation()->registerByPublication($publication, $thothImprintId);

        return $thothBookId;
    }

    public function deleteRegisteredEntry(): void
    {
        if ($this->registeredEntryId === null) {
            return;
        }

        $this->repository->delete($this->registeredEntryId);
        $this->registeredEntryId = null;
    }

    public function setActive(): void
    {
        if ($this->originalThothBook === null) {
            return;
        }

        $this->originalThothBook->setWorkId($this->registeredEntryId);
        $this->originalThothBook->setWorkStatus(WorkStatus::ACTIVE);
        $this->repository->edit($this->originalThothBook);
    }

    private function registerMetadata($publication, string $thothBookId): void
    {
        ThothService::title()->registerByPublication(
            $publication,
            $thothBookId,
            $publication->getData('locale')
        );
        ThothService::abstract()->registerByPublication(
            $publication,
            $thothBookId,
            $publication->getData('locale')
        );
    }
}
