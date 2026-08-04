<?php

import('lib.pkp.classes.form.Form');

use Illuminate\Database\Capsule\Manager as Capsule;

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

        $preview = $this->createPreviewState(array_column($this->previewSubmissions, 'id'), $deletionThreshold);
        $this->previewId = $preview['id'];
        $request->getSession()->setSessionVar($this->getPreviewSessionKey(), $preview);
    }

    public function hasValidPreview($request): bool
    {
        $preview = $request->getSession()->getSessionVar($this->getPreviewSessionKey());
        $previewId = $this->getData('previewId');

        return is_array($preview) && $this->isPreviewStateValid(
            $preview,
            is_string($previewId) ? $previewId : null,
            (int) $this->getData('deletionThreshold')
        );
    }

    public function execute(...$functionArgs)
    {
        $request = Application::get()->getRequest();
        $preview = $this->consumePreviewState($request);
        if ($preview === null) {
            return 0;
        }

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
            try {
                $wasDeleted = Capsule::connection()->transaction(function () use ($submissionId, $policy, $submissionService): bool {
                    if (!$this->lockSubmissionForDeletion($submissionId)) {
                        return false;
                    }

                    $submission = $submissionService->get($submissionId);
                    if (!$submission || !$policy->allows($submission)) {
                        return false;
                    }

                    $submissionService->delete($submission);
                    return true;
                });

                if ($wasDeleted) {
                    $deletedCount++;
                    error_log(
                        'Incomplete submission deleted after preview and safety revalidation. Submission ID: '
                        . $submissionId
                    );
                } else {
                    error_log(
                        'Incomplete submission deletion skipped after safety revalidation. Submission ID: '
                        . $submissionId
                    );
                }
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

    /**
     * @param int[] $submissionIds
     * @return array{id: string, createdAt: int, deletionThreshold: int, submissionIds: int[]}
     */
    private function createPreviewState(array $submissionIds, int $deletionThreshold): array
    {
        return [
            'id' => bin2hex(random_bytes(16)),
            'createdAt' => time(),
            'deletionThreshold' => $deletionThreshold,
            'submissionIds' => array_values(array_unique(array_filter(
                array_map('intval', $submissionIds),
                function (int $submissionId): bool {
                    return $submissionId > 0;
                }
            ))),
        ];
    }

    /**
     * @param array{id: string, createdAt: int, deletionThreshold: int, submissionIds: int[]} $preview
     */
    private function isPreviewStateValid(array $preview, ?string $previewId, int $deletionThreshold): bool
    {
        return is_string($previewId)
            && hash_equals($preview['id'], $previewId)
            && time() - $preview['createdAt'] <= self::PREVIEW_TTL_SECONDS
            && $preview['deletionThreshold'] === $deletionThreshold;
    }

    /**
     * @return array{id: string, createdAt: int, deletionThreshold: int, submissionIds: int[]}|null
     */
    private function consumePreviewState($request): ?array
    {
        $preview = $request->getSession()->getSessionVar($this->getPreviewSessionKey());
        if (!is_array($preview) || !$this->hasValidPreview($request)) {
            return null;
        }

        $request->getSession()->unsetSessionVar($this->getPreviewSessionKey());
        return $preview;
    }

    private function lockSubmissionForDeletion(int $submissionId): bool
    {
        $connection = Capsule::connection();
        $submission = $connection->table('submissions')
            ->where('submission_id', $submissionId)
            ->where('context_id', $this->contextId)
            ->lockForUpdate()
            ->first();
        if ($submission === null) {
            return false;
        }

        $publicationIds = $connection->table('publications')
            ->where('submission_id', $submissionId)
            ->lockForUpdate()
            ->pluck('publication_id')
            ->all();
        if ($publicationIds !== []) {
            $connection->table('publication_galleys')
                ->whereIn('publication_id', $publicationIds)
                ->lockForUpdate()
                ->get();
        }

        return true;
    }
}
