<?php

declare(strict_types=1);

class SubmissionDeletionPreview
{
    /** @var int */
    private $ttlSeconds;

    public function __construct(int $ttlSeconds)
    {
        $this->ttlSeconds = $ttlSeconds;
    }

    /**
     * @param int[] $submissionIds
     * @return array{id: string, createdAt: int, deletionThreshold: int, submissionIds: int[]}
     */
    public function create(array $submissionIds, int $deletionThreshold, int $createdAt): array
    {
        $submissionIds = array_values(array_unique(array_filter(
            array_map('intval', $submissionIds),
            function (int $submissionId): bool {
                return $submissionId > 0;
            }
        )));

        return [
            'id' => bin2hex(random_bytes(16)),
            'createdAt' => $createdAt,
            'deletionThreshold' => $deletionThreshold,
            'submissionIds' => $submissionIds,
        ];
    }

    public function isValid(
        array $preview,
        ?string $previewId,
        int $deletionThreshold,
        int $currentTimestamp
    ): bool {
        if (
            !isset(
                $preview['id'],
                $preview['createdAt'],
                $preview['deletionThreshold'],
                $preview['submissionIds']
            )
            || !is_string($preview['id'])
            || !preg_match('/^[a-f0-9]{32}$/', $preview['id'])
            || !is_string($previewId)
            || !preg_match('/^[a-f0-9]{32}$/', $previewId)
            || !is_int($preview['createdAt'])
            || $preview['createdAt'] > $currentTimestamp
            || $currentTimestamp - $preview['createdAt'] > $this->ttlSeconds
            || $preview['deletionThreshold'] !== $deletionThreshold
            || !is_array($preview['submissionIds'])
        ) {
            return false;
        }

        foreach ($preview['submissionIds'] as $submissionId) {
            if (!is_int($submissionId) || $submissionId <= 0) {
                return false;
            }
        }

        return hash_equals($preview['id'], $previewId);
    }
}
