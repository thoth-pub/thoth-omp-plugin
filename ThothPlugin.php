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
        return __('plugins.generic.thoth.name');
    }

    public function getDescription()
    {
        return __('plugins.generic.thoth.description');
    }
}
