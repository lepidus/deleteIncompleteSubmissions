<?php

namespace APP\plugins\generic\deleteIncompleteSubmissions\settings;

use APP\template\TemplateManager;
use PKP\form\Form;
use PKP\form\validation\FormValidatorCSRF;
use PKP\form\validation\FormValidatorPost;
use APP\core\Application;
use APP\facades\Repo;

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
        $deletionThreshold = $this->getData('deletionThreshold');

        $this->deleteIncompleteSubmissions($deletionThreshold);
        parent::execute(...$functionArgs);
    }

    private function deleteIncompleteSubmissions(int $deletionThreshold): void
    {
        $submissions = Repo::submission()
            ->getCollector()
            ->filterByContextIds([$this->contextId])
            ->filterByIncomplete(true)
            ->filterByDaysInactive($deletionThreshold)
            ->getMany();

        try {
            foreach ($submissions as $submission) {
                Repo::submission()->delete($submission);
            }
        } catch (\Throwable $th) {
            error_log('The submission  ' . $submission->getId() . ' was not deleted. Reason:' . $th->getMessage());
        }
    }
}
