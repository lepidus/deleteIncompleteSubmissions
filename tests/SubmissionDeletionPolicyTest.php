<?php

namespace APP\plugins\generic\deleteIncompleteSubmissions\tests;

use APP\plugins\generic\deleteIncompleteSubmissions\classes\SubmissionDeletionPolicy;
use APP\publication\Publication;
use APP\submission\Submission;
use PHPUnit\Framework\TestCase;
use PKP\galley\Galley;

class SubmissionDeletionPolicyTest extends TestCase
{
    private const CONTEXT_ID = 7;
    private const CUTOFF_TIMESTAMP = 1_700_000_000;

    public function testAllowsGenuinelyIncompleteSubmission(): void
    {
        $this->assertTrue($this->policy()->allows($this->submission()));
    }

    public function testRejectsSubmissionWhenInvariantIsNotMet(): void
    {
        foreach ($this->unsafeSubmissionProvider() as [$submission, $publications]) {
            $this->assertFalse($this->policy()->allows($this->submission($submission, $publications)));
        }
    }

    public function unsafeSubmissionProvider(): array
    {
        return [
            'published submission with stale progress' => [
                $this->submissionData(['status' => Submission::STATUS_PUBLISHED]),
                [$this->publicationData()],
            ],
            'scheduled submission with stale progress' => [
                $this->submissionData(['status' => Submission::STATUS_SCHEDULED]),
                [$this->publicationData()],
            ],
            'declined submission with stale progress' => [
                $this->submissionData(['status' => Submission::STATUS_DECLINED]),
                [$this->publicationData()],
            ],
            'already completed submission' => [
                $this->submissionData(['submissionProgress' => '']),
                [$this->publicationData()],
            ],
            'invalid submission progress type' => [
                $this->submissionData(['submissionProgress' => 1]),
                [$this->publicationData()],
            ],
            'submission from another context' => [
                $this->submissionData(['contextId' => self::CONTEXT_ID + 1]),
                [$this->publicationData()],
            ],
            'recently active submission' => [
                $this->submissionData(['dateLastActivity' => '2023-11-20 00:00:00']),
                [$this->publicationData()],
            ],
            'invalid last activity date' => [
                $this->submissionData(['dateLastActivity' => 'not-a-date']),
                [$this->publicationData()],
            ],
            'missing current publication' => [
                $this->submissionData(['currentPublicationId' => null]),
                [$this->publicationData()],
            ],
            'current publication not loaded' => [
                $this->submissionData(),
                [$this->publicationData(['id' => 99])],
            ],
            'publication collection is empty' => [
                $this->submissionData(),
                [],
            ],
            'published current publication' => [
                $this->submissionData(),
                [$this->publicationData(['status' => Submission::STATUS_PUBLISHED])],
            ],
            'scheduled current publication' => [
                $this->submissionData(),
                [$this->publicationData(['status' => Submission::STATUS_SCHEDULED])],
            ],
            'published previous publication' => [
                $this->submissionData(),
                [
                    $this->publicationData(),
                    $this->publicationData([
                        'id' => 11,
                        'status' => Submission::STATUS_PUBLISHED,
                    ]),
                ],
            ],
            'publication with assigned DOI' => [
                $this->submissionData(),
                [$this->publicationData(['doiId' => 42])],
            ],
            'galley with assigned DOI' => [
                $this->submissionData(),
                [$this->publicationData([
                    'galleys' => [$this->galley(['doiId' => 43])],
                ])],
            ],
            'publication with galleys that were not loaded' => [
                $this->submissionData(),
                [$this->publicationData(['galleys' => null])],
            ],
        ];
    }

    private function policy(): SubmissionDeletionPolicy
    {
        return new SubmissionDeletionPolicy(self::CONTEXT_ID, self::CUTOFF_TIMESTAMP);
    }

    private function submissionData(array $overrides = []): array
    {
        return array_merge([
            'status' => Submission::STATUS_QUEUED,
            'submissionProgress' => 'files',
            'contextId' => self::CONTEXT_ID,
            'dateLastActivity' => '2023-01-01 00:00:00',
            'currentPublicationId' => 10,
        ], $overrides);
    }

    private function submission(array $overrides = [], ?array $publications = null): Submission
    {
        $submission = new Submission();
        $submission->setAllData(array_merge(
            $this->submissionData(),
            $overrides,
            [
                'publications' => array_map(
                    fn (array $publication): Publication => $this->publication($publication),
                    $publications ?? [$this->publicationData()]
                ),
            ]
        ));

        return $submission;
    }

    private function publication(array $data): Publication
    {
        $publication = new Publication();
        $publication->setAllData($data);

        return $publication;
    }

    private function galley(array $data): Galley
    {
        $galley = new Galley();
        $galley->setAllData($data);

        return $galley;
    }

    private function publicationData(array $overrides = []): array
    {
        return array_merge([
            'id' => 10,
            'status' => Submission::STATUS_QUEUED,
            'doiId' => null,
            'galleys' => [],
        ], $overrides);
    }
}
