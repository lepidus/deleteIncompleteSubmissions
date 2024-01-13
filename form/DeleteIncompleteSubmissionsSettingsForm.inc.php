<?php

/**
 * @file plugins/generic/deleteIncompleteSubmissions/DeleteIncompleteSubmissionsSettingsForm.inc.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Distributed under the GNU GPL v3. For full terms see LICENSE or https://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @class DeleteIncompleteSubmissionsSettingsForm
 * @ingroup plugins_generic_toggleRequiredMetadata
 */

import('lib.pkp.classes.form.Form');

class DeleteIncompleteSubmissionsSettingsForm extends Form
{
    public $plugin;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
        parent::__construct($plugin->getTemplateResource("settingsForm.tpl"));
        $this->addCheck(new FormValidatorCSRF($this));
    }

    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign(array(
            "pluginName" => $this->plugin->getName(),
        ));

        return parent::fetch($request, $template, $display);
    }

    public function execute(...$functionArgs)
    {
        parent::execute(...$functionArgs);
    }
}
