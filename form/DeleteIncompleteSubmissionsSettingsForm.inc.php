<?php

import('lib.pkp.classes.form.Form');

class DeleteIncompleteSubmissionsSettingsForm extends Form
{
    private const PREVIEW_TTL_SECONDS = 900;

    public const FORM_VARS = [
        'deletionThreshold' => 'integer',
        'previewId' => 'string',
    ];

    public $contextId;
    public $plugin;

    /** @var array */
    private $previewSubmissions = [];

    /** @var bool */
    private $isPreview = false;

    /** @var string|null */
    private $previewId = null;

    public function __construct($plugin, $contextId)
    {
        $this->contextId = $contextId;
        $this->plugin = $plugin;
        $this->plugin->import('classes.SubmissionDeletionPolicy');
        $this->plugin->import('classes.SubmissionDeletionPreview');
        parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
        $this->addCheck(new FormValidatorCustom(
            $this,
            'deletionThreshold',
            'required',
            'plugins.generic.deleteIncompleteSubmissions.validation.integer',
            function ($deletionThreshold) {
                if (is_int($deletionThreshold)) {
                    return $deletionThreshold > 0;
                }

                if (!is_string($deletionThreshold) || !preg_match('/^\d+$/', $deletionThreshold)) {
                    return false;
                }

                return (int) $deletionThreshold > 0;
            }
        ));
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
        $templateMgr->assign('defaultThreshold', 15);
        $templateMgr->assign('isPreview', $this->isPreview);
        $templateMgr->assign('previewSubmissions', $this->previewSubmissions);
        $templateMgr->assign('previewId', $this->previewId);

        return parent::fetch($request, $template, $display);
    }

    public function preparePreview($request): void
    {
        $deletionThreshold = (int) $this->getData('deletionThreshold');
        $submissions = $this->getEligibleSubmissions($deletionThreshold);

        $this->previewSubmissions = array_map(
            function (Submission $submission): array {
                return [
                    'id' => $submission->getId(),
                    'title' => (string) $submission->getLocalizedFullTitle(),
                    'status' => __($submission->getStatusKey()),
                    'dateLastActivity' => (string) $submission->getData('dateLastActivity'),
                ];
            },
            $submissions
        );
        $this->isPreview = true;

        $preview = $this->getPreviewManager()->create(
            array_column($this->previewSubmissions, 'id'),
            $deletionThreshold,
            time()
        );
        $this->previewId = $preview['id'];
        $request->getSession()->setSessionVar($this->getPreviewSessionKey(), $preview);
    }

    public function hasValidPreview($request): bool
    {
        $preview = $request->getSession()->getSessionVar($this->getPreviewSessionKey());
        $previewId = $this->getData('previewId');

        return is_array($preview) && $this->getPreviewManager()->isValid(
            $preview,
            is_string($previewId) ? $previewId : null,
            (int) $this->getData('deletionThreshold'),
            time()
        );
    }

    public function execute(...$functionArgs)
    {
        $request = Application::get()->getRequest();
        if (!$this->hasValidPreview($request)) {
            return 0;
        }

        $preview = $request->getSession()->getSessionVar($this->getPreviewSessionKey());
        $request->getSession()->unsetSessionVar($this->getPreviewSessionKey());

        $deletedCount = $this->deleteIncompleteSubmissions(
            $preview['submissionIds'],
            (int) $preview['deletionThreshold']
        );

        parent::execute(...$functionArgs);
        return $deletedCount;
    }

    /** @return Submission[] */
    private function getEligibleSubmissions(int $deletionThreshold): array
    {
        $submissions = Services::get('submission')->getMany([
            'contextId' => $this->contextId,
            'status' => STATUS_QUEUED,
            'isIncomplete' => true,
            'daysInactive' => $deletionThreshold,
        ]);

        $policy = $this->getDeletionPolicy($deletionThreshold);
        $eligibleSubmissions = [];
        foreach ($submissions as $submission) {
            if ($policy->allows($submission)) {
                $eligibleSubmissions[] = $submission;
            }
        }

        return $eligibleSubmissions;
    }

    /** @param int[] $submissionIds */
    private function deleteIncompleteSubmissions(array $submissionIds, int $deletionThreshold): int
    {
        $deletedCount = 0;
        $submissionService = Services::get('submission');
        $policy = $this->getDeletionPolicy($deletionThreshold);

        foreach (array_unique(array_map('intval', $submissionIds)) as $submissionId) {
            $submission = $submissionService->get($submissionId);
            if (
                !$submission
                || $submission->getData('contextId') !== $this->contextId
                || !$policy->allows($submission)
            ) {
                error_log(
                    'Incomplete submission deletion skipped after safety revalidation. Submission ID: '
                    . $submissionId
                );
                continue;
            }

            try {
                $submissionService->delete($submission);
                $deletedCount++;
                error_log(
                    'Incomplete submission deleted after preview and safety revalidation. Submission ID: '
                    . $submissionId
                );
            } catch (\Throwable $th) {
                error_log('The submission ' . $submissionId . ' was not deleted. Reason: ' . $th->getMessage());
            }
        }

        return $deletedCount;
    }

    private function getDeletionPolicy(int $deletionThreshold): SubmissionDeletionPolicy
    {
        $cutoffTimestamp = strtotime('-' . $deletionThreshold . ' days');
        if ($cutoffTimestamp === false) {
            throw new RuntimeException('Unable to calculate the incomplete submission deletion threshold.');
        }

        return new SubmissionDeletionPolicy($this->contextId, $cutoffTimestamp);
    }

    private function getPreviewSessionKey(): string
    {
        return 'deleteIncompleteSubmissionsPreview-' . $this->contextId;
    }

    private function getPreviewManager(): SubmissionDeletionPreview
    {
        return new SubmissionDeletionPreview(self::PREVIEW_TTL_SECONDS);
    }
}
