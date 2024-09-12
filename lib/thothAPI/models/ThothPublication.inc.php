<?php

/**
 * @file plugins/generic/thoth/lib/thothAPI/models/ThothPublication.inc.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothPublication
 * @ingroup plugins_generic_thoth
 *
 * @brief Class for a Thoth's publication.
 */

import('plugins.generic.thoth.lib.thothAPI.models.ThothModel');

class ThothPublication extends ThothModel
{
    private $publicationId;

    private $workId;

    private $publicationType;

    private $isbn;

    public const PUBLICATION_TYPE_PAPERBACK = 'PAPERBACK';

    public const PUBLICATION_TYPE_HARDBACK = 'HARDBACK';

    public const PUBLICATION_TYPE_HTML = 'HTML';

    public const PUBLICATION_TYPE_PDF = 'PDF';

    public const PUBLICATION_TYPE_XML = 'XML';

    public const PUBLICATION_TYPE_EPUB = 'EPUB';

    public const PUBLICATION_TYPE_MOBI = 'MOBI';

    public const PUBLICATION_TYPE_AZW3 = 'AZW3';

    public const PUBLICATION_TYPE_DOCX = 'DOCX';

    public const PUBLICATION_TYPE_FICTION_BOOK = 'FICTION_BOOK';

    public function getEnumeratedValues()
    {
        return parent::getEnumeratedValues() + [
            'publicationType'
        ];
    }

    public function getReturnValue()
    {
        return 'publicationId';
    }

    public function getId()
    {
        return $this->publicationId;
    }

    public function setId($publicationId)
    {
        $this->publicationId = $publicationId;
    }

    public function getWorkId()
    {
        return $this->workId;
    }

    public function setWorkId($workId)
    {
        $this->workId = $workId;
    }

    public function getPublicationType()
    {
        return $this->publicationType;
    }

    public function setPublicationType($publicationType)
    {
        $this->publicationType = $publicationType;
    }

    public function getIsbn()
    {
        return $this->isbn;
    }

    public function setIsbn($isbn)
    {
        $this->isbn = $isbn;
    }
}
