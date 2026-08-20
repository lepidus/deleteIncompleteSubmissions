<?php

/**
 * @file plugins/generic/deleteIncompleteSubmissions/deleteIncompleteSubmissions.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Distributed under the GNU GPL v3. For full terms see LICENSE or https://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @class DeleteIncompleteSubmissionsPlugin
 * @ingroup plugins_generic_deleteIncompleteSubmissions
 *
 */

namespace APP\plugins\generic\deleteIncompleteSubmissions;

use APP\core\Application;
use APP\plugins\generic\deleteIncompleteSubmissions\settings\DeleteIncompleteSubmissionsSettingsForm;
use PKP\core\JSONMessage;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\plugins\GenericPlugin;

class DeleteIncompleteSubmissionsPlugin extends GenericPlugin
{
    public function register($category, $path, $mainContextId = null)
    {
        return parent::register($category, $path, $mainContextId);
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

    public function getActions($request, $actionArgs)
    {
        $router = $request->getRouter();
        return array_merge(
            $this->getEnabled() ? [
                new LinkAction(
                    'deletion',
                    new AjaxModal($router->url($request, null, null, 'manage', null, ['verb' => 'deletion', 'plugin' => $this->getName(), 'category' => 'generic']), $this->getDisplayName()),
                    __('plugins.generic.deleteIncompleteSubmissions.deletion'),
                )
            ] : [],
            parent::getActions($request, $actionArgs)
        );
    }

    public function manage($args, $request)
    {
        switch ($request->getUserVar('verb')) {
            case 'deletion':
                $context = $request->getContext();
                $form = new DeleteIncompleteSubmissionsSettingsForm($this, $context->getId());

                if ($request->getUserVar('save')) {
                    $form->readInputData();
                    if ($form->validate()) {
                        if ($request->getUserVar('deletionAction') === 'confirm') {
                            if ($form->execute()) {
                                return new JSONMessage(true);
                            }

                            $form->addError(
                                'deletionThreshold',
                                __('plugins.generic.deleteIncompleteSubmissions.preview.expired')
                            );
                        } else {
                            $form->preparePreview($request);
                        }
                    }
                }

                return new JSONMessage(true, $form->fetch($request));
            default:
                return parent::manage($args, $request);
        }
    }
}
