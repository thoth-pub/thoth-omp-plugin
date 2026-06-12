<?php

/**
 * @file controllers/fileUpload/form/UploadThothPublicationFileForm.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class UploadThothPublicationFileForm
 * @ingroup plugins_generic_thoth
 *
 * @brief Form for uploading publication files to Thoth.
 */

import('lib.pkp.classes.form.Form');

class UploadThothPublicationFileForm extends Form
{
    public function __construct($template, $contextId, $publicationId, $publicationFormatId)
    {
        parent::__construct($template);
    }
}
