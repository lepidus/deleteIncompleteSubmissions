<?php

declare(strict_types=1);

class SubmissionDeletionPolicy
{
    private $contextId;

    private $cutoffTimestamp;

    public function __construct(int $contextId, int $cutoffTimestamp)
    {
        $this->contextId = $contextId;
        $this->cutoffTimestamp = $cutoffTimestamp;
    }

    public function allows(Submission $submission): bool
    {
        $submissionProgress = $submission->getData('submissionProgress');
        $dateLastActivity = $submission->getData('dateLastActivity');
        $currentPublicationId = $submission->getData('currentPublicationId');

        if (
            $submission->getData('status') !== STATUS_QUEUED
            || !is_int($submissionProgress)
            || $submissionProgress <= 0
            || $submission->getData('contextId') !== $this->contextId
            || !is_int($currentPublicationId)
            || !is_string($dateLastActivity)
        ) {
            return false;
        }

        $lastActivityTimestamp = strtotime($dateLastActivity);
        if ($lastActivityTimestamp === false || $lastActivityTimestamp >= $this->cutoffTimestamp) {
            return false;
        }

        $publications = $submission->getData('publications');
        if (!is_iterable($publications)) {
            return false;
        }

        $hasCurrentPublication = false;
        foreach ($publications as $publication) {
            if (!is_int($publication->getId()) || !is_int($publication->getData('status'))) {
                return false;
            }

            if ($publication->getId() === $currentPublicationId) {
                $hasCurrentPublication = true;
            }

            if (
                in_array($publication->getData('status'), [STATUS_PUBLISHED, STATUS_SCHEDULED], true)
                || $publication->getStoredPubId('doi')
            ) {
                return false;
            }

            $galleys = $publication->getData('galleys');
            if (!is_iterable($galleys)) {
                return false;
            }

            foreach ($galleys as $galley) {
                if ($galley->getStoredPubId('doi')) {
                    return false;
                }
            }
        }

        return $hasCurrentPublication;
    }
}
