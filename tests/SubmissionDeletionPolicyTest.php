<?php

namespace APP\plugins\generic\deleteIncompleteSubmissions\tests;

use APP\plugins\generic\deleteIncompleteSubmissions\classes\SubmissionDeletionPolicy;
use APP\submission\Submission;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/classes/SubmissionDeletionPolicy.php';

class SubmissionDeletionPolicyTest extends TestCase
{
    private const CONTEXT_ID = 7;
    private const CUTOFF_TIMESTAMP = 1_700_000_000;

    public function testAllowsGenuinelyIncompleteSubmission(): void
    {
        $this->assertTrue($this->policy()->allows($this->submission()));
    }

    /**
     * @dataProvider unsafeSubmissionProvider
     */
    public function testRejectsSubmissionWhenInvariantIsNotMet(
        array $submission,
        array $publications
    ): void {
        $this->assertFalse($this->policy()->allows($this->submission($submission, $publications)));
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
                    'galleys' => [new FakeDataObject(['doiId' => 43])],
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
        return new Submission(array_merge(
            $this->submissionData(),
            $overrides,
            [
                'publications' => array_map(
                    fn (array $publication): FakePublication => new FakePublication($publication),
                    $publications ?? [$this->publicationData()]
                ),
            ]
        ));
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

class FakeDataObject
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getData(string $name)
    {
        return $this->data[$name] ?? null;
    }
}

class FakePublication extends FakeDataObject
{
    public function getId(): int
    {
        return $this->data['id'];
    }
}
