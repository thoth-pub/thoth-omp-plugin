<?php

import('lib.pkp.classes.plugins.GenericPlugin');

class ThothPlugin extends GenericPlugin
{
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path);

        if ($success && $this->getEnabled()) {
        }

        return $success;
    }

    public function getDisplayName()
    {
        return 'Thoth';
    }

    public function getDescription()
    {
        return 'Integration of OMP and Thoth for communication and synchronization of book data between the two platforms.';
    }
}
