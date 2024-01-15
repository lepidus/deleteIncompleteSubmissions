<?php

import('lib.pkp.classes.plugins.GenericPlugin');

class DeleteIncompleteSubmissionsPlugin extends GenericPlugin
{
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path);

        if ($success && $this->getEnabled()) {
            // HookRegistry::register('', array($this, ''));
        }

        return $success;
    }

    public function getDisplayName(): string
    {
        return __('plugins.generic.deleteIncompleteSubmissions.displayName');
    }

    public function getDescription(): string
    {
        return __('plugins.generic.deleteIncompleteSubmissions.description');
    }

    public function getCanEnable()
    {
        return ((bool) Application::get()->getRequest()->getContext());
    }

    public function getCanDisable()
    {
        return ((bool) Application::get()->getRequest()->getContext());
    }
}
