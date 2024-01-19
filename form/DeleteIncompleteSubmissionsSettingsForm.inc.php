<?php

import('lib.pkp.classes.form.Form');

class DeleteIncompleteSubmissionsSettingsForm extends Form
{
    public const FORM_VARS = [
        'deletionThreshold' => 'integer',
    ];

    public $contextId;
    public $plugin;

    public function __construct($plugin, $contextId)
    {
        $this->contextId = $contextId;
        $this->plugin = $plugin;
        parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
    }

    public function initData()
    {
        $contextId = $this->contextId;
        $plugin = &$this->plugin;
        $this->_data = array();
    }

    public function readInputData()
    {
        $this->readUserVars(array_keys(self::FORM_VARS));
    }

    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('pluginName', $this->plugin->getName());
        $templateMgr->assign('applicationName', Application::get()->getName());
        $templateMgr->assign('thresholdValues', range(0, 60));
        $templateMgr->assign('defaultThreshold', 15);

        return parent::fetch($request, $template, $display);
    }

    public function execute(...$functionArgs)
    {
        $plugin = &$this->plugin;
        $contextId = $this->contextId;

        $deletionThreshold = $this->getData('deletionThreshold');

        $this->deleteIncompleteSubmissions($deletionThreshold);
        parent::execute(...$functionArgs);
    }

    private function deleteIncompleteSubmissions(int $deletionThreshold): void
    {
        $submissionService = Services::get('submission');
        $submissions = $submissionService->getMany([
            'contextId' => $this->contextId, 'isIncomplete' => true, 'daysInactive' => $deletionThreshold
        ]);

        foreach ($submissions as $submission) {
            try {
                $submissionService->delete($submission);
            } catch (\Throwable $th) {
                error_log('The submission  ' . $submission->getId() . ' was not deleted. Reason:' . $th->getMessage());
            }
        }
    }
}
