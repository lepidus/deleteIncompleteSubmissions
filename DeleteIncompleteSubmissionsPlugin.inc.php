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

    public function getName(): string
    {
        return 'DeleteIncompleteSubmissionsPlugin';
    }

    public function getDisplayName(): string
    {
        return __('plugins.generic.deleteIncompleteSubmissions.displayName');
    }

    public function getDescription(): string
    {
        return __('plugins.generic.deleteIncompleteSubmissions.description');
    }

    public function manage($args, $request)
    {
        switch ($request->getUserVar('verb')) {
            case 'settings':
                $this->import('form.DeleteIncompleteSubmissionsSettingsForm');
                $form = new DeleteIncompleteSubmissionsSettingsForm($this);

                if ($request->getUserVar('save')) {
                    $form->readInputData();
                    if ($form->validate()) {
                        $form->execute();
                        return new JSONMessage(true);
                    }
                }
                return new JSONMessage(true, $form->fetch($request));
            default:
                return parent::manage($verb, $args, $message, $messageParams);
        }
    }
}
