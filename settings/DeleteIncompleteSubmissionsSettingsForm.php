<?php

namespace APP\plugins\generic\deleteIncompleteSubmissions\settings;

use APP\core\Application;
use APP\facades\Repo;
use APP\plugins\generic\deleteIncompleteSubmissions\classes\SubmissionDeletionPolicy;
use APP\submission\Submission;
use APP\template\TemplateManager;
use Illuminate\Support\Facades\DB;
use PKP\form\Form;
use PKP\form\validation\FormValidatorCSRF;
use PKP\form\validation\FormValidatorCustom;
use PKP\form\validation\FormValidatorPost;

class DeleteIncompleteSubmissionsSettingsForm extends Form
{
    private const PREVIEW_TTL_SECONDS = 900;

    public const FORM_VARS = [
        'deletionThreshold' => 'integer',
        'previewId' => 'string',
    ];

    public $contextId;
    public $plugin;
    private array $previewSubmissions = [];
    private bool $isPreview = false;
    private ?string $previewId = null;

    public function __construct($plugin, $contextId)
    {
        $this->contextId = $contextId;
        $this->plugin = $plugin;
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
        $previewId = $this->previewId;
        if ($this->isPreview) {
            $preview = $request->getSession()->getSessionVar($this->getPreviewSessionKey());
            if (is_array($preview) && is_string($preview['id'] ?? null)) {
                $previewId = $preview['id'];
            }
        }
        $this->setData('previewId', $previewId);

        $templateMgr->assign('pluginName', $this->plugin->getName());
        $templateMgr->assign('applicationName', Application::get()->getName());
        $templateMgr->assign('defaultThreshold', 15);
        $templateMgr->assign('isPreview', $this->isPreview);
        $templateMgr->assign('previewSubmissions', $this->previewSubmissions);
        $templateMgr->assign('previewId', $previewId);

        return parent::fetch($request, $template, $display);
    }

    public function preparePreview($request): void
    {
        $deletionThreshold = (int) $this->getData('deletionThreshold');
        $submissions = $this->getEligibleSubmissions($deletionThreshold);

        $this->previewSubmissions = array_map(
            fn (Submission $submission): array => [
                'id' => $submission->getId(),
                'title' => (string) $submission->getLocalizedFullTitle(),
                'status' => __($submission->getStatusKey()),
                'dateLastActivity' => (string) $submission->getData('dateLastActivity'),
            ],
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

        $deletionThreshold = (int) $preview['deletionThreshold'];
        $deletedCount = $this->deleteIncompleteSubmissions(
            $preview['submissionIds'],
            $deletionThreshold
        );

        parent::execute(...$functionArgs);
        return $deletedCount;
    }

    private function getEligibleSubmissions(int $deletionThreshold): array
    {
        $submissions = Repo::submission()
            ->getCollector()
            ->filterByContextIds([$this->contextId])
            ->filterByStatus([Submission::STATUS_QUEUED])
            ->filterByIncomplete(true)
            ->filterByDaysInactive($deletionThreshold)
            ->getMany();

        $policy = $this->getDeletionPolicy($deletionThreshold);
        $eligibleSubmissions = [];
        foreach ($submissions as $submission) {
            if ($policy->allows($submission)) {
                $eligibleSubmissions[] = $submission;
            }
        }

        return $eligibleSubmissions;
    }

    private function deleteIncompleteSubmissions(array $submissionIds, int $deletionThreshold): int
    {
        $deletedCount = 0;
        $policy = $this->getDeletionPolicy($deletionThreshold);

        foreach (array_unique(array_map('intval', $submissionIds)) as $submissionId) {
            try {
                $wasDeleted = DB::transaction(function () use ($submissionId, $policy): bool {
                    if (!$this->lockSubmissionForDeletion($submissionId)) {
                        return false;
                    }

                    $submission = Repo::submission()->get($submissionId, $this->contextId);
                    if (!$submission || !$policy->allows($submission)) {
                        return false;
                    }

                    Repo::submission()->delete($submission);
                    return true;
                });

                if ($wasDeleted) {
                    $deletedCount++;
                    error_log('Incomplete submission deleted after preview and safety revalidation. Submission ID: ' . $submissionId);
                } else {
                    error_log('Incomplete submission deletion skipped after safety revalidation. Submission ID: ' . $submissionId);
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
            throw new \RuntimeException('Unable to calculate the incomplete submission deletion threshold.');
        }

        return new SubmissionDeletionPolicy($this->contextId, $cutoffTimestamp);
    }

    private function getPreviewSessionKey(): string
    {
        return 'deleteIncompleteSubmissionsPreview-' . $this->contextId;
    }

    private function createPreviewState(array $submissionIds, int $deletionThreshold): array
    {
        return [
            'id' => bin2hex(random_bytes(16)),
            'createdAt' => time(),
            'deletionThreshold' => $deletionThreshold,
            'submissionIds' => array_values(array_unique(array_filter(
                array_map('intval', $submissionIds),
                fn (int $submissionId): bool => $submissionId > 0
            ))),
        ];
    }

    private function isPreviewStateValid(array $preview, ?string $previewId, int $deletionThreshold): bool
    {
        return is_string($previewId)
            && hash_equals($preview['id'], $previewId)
            && time() - $preview['createdAt'] <= self::PREVIEW_TTL_SECONDS
            && $preview['deletionThreshold'] === $deletionThreshold;
    }

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
        $submission = DB::table('submissions')
            ->where('submission_id', $submissionId)
            ->where('context_id', $this->contextId)
            ->lockForUpdate()
            ->first();
        if ($submission === null) {
            return false;
        }

        $publicationIds = DB::table('publications')
            ->where('submission_id', $submissionId)
            ->lockForUpdate()
            ->pluck('publication_id')
            ->all();
        if ($publicationIds !== []) {
            $representationsTable = Application::get()->getName() === 'omp' ? 'publication_formats' : 'publication_galleys';
            DB::table($representationsTable)
                ->whereIn('publication_id', $publicationIds)
                ->lockForUpdate()
                ->get();
        }

        return true;
    }
}
