<?php

declare(strict_types=1);

namespace APP\plugins\generic\deleteIncompleteSubmissions\classes;

use APP\submission\Submission;

class SubmissionDeletionPolicy
{
    private int $contextId;
    private int $cutoffTimestamp;

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
            $submission->getData('status') !== Submission::STATUS_QUEUED
            || !is_string($submissionProgress)
            || $submissionProgress === ''
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
                in_array(
                    $publication->getData('status'),
                    [Submission::STATUS_PUBLISHED, Submission::STATUS_SCHEDULED],
                    true
                )
                || $publication->getData('doiId') !== null
            ) {
                return false;
            }

            $galleys = $publication->getData('galleys');
            if (!is_iterable($galleys)) {
                return false;
            }

            foreach ($galleys as $galley) {
                if ($galley->getData('doiId') !== null) {
                    return false;
                }
            }
        }

        return $hasCurrentPublication;
    }
}
