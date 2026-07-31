<?php

namespace APP\plugins\generic\deleteIncompleteSubmissions\tests;

use APP\plugins\generic\deleteIncompleteSubmissions\classes\SubmissionDeletionPreview;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/classes/SubmissionDeletionPreview.php';

class SubmissionDeletionPreviewTest extends TestCase
{
    private const NOW = 1_700_000_000;
    private const THRESHOLD = 15;

    public function testCreatedPreviewIsValidForItsIdentifier(): void
    {
        $manager = new SubmissionDeletionPreview(900);
        $preview = $manager->create([10, 20], self::THRESHOLD, self::NOW);

        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $preview['id']);
        $this->assertTrue($manager->isValid(
            $preview,
            $preview['id'],
            self::THRESHOLD,
            self::NOW
        ));
    }

    public function testNewPreviewMakesIdentifierFromAnotherTabStale(): void
    {
        $manager = new SubmissionDeletionPreview(900);
        $firstTabPreview = $manager->create([10], self::THRESHOLD, self::NOW);
        $currentPreview = $manager->create([20], self::THRESHOLD, self::NOW + 1);

        $this->assertNotSame($firstTabPreview['id'], $currentPreview['id']);
        $this->assertFalse($manager->isValid(
            $currentPreview,
            $firstTabPreview['id'],
            self::THRESHOLD,
            self::NOW + 1
        ));
        $this->assertTrue($manager->isValid(
            $currentPreview,
            $currentPreview['id'],
            self::THRESHOLD,
            self::NOW + 1
        ));
    }

    public function testRejectsExpiredPreview(): void
    {
        $manager = new SubmissionDeletionPreview(900);
        $preview = $manager->create([10], self::THRESHOLD, self::NOW);

        $this->assertFalse($manager->isValid(
            $preview,
            $preview['id'],
            self::THRESHOLD,
            self::NOW + 901
        ));
    }

    public function testRejectsChangedThreshold(): void
    {
        $manager = new SubmissionDeletionPreview(900);
        $preview = $manager->create([10], self::THRESHOLD, self::NOW);

        $this->assertFalse($manager->isValid(
            $preview,
            $preview['id'],
            self::THRESHOLD + 1,
            self::NOW
        ));
    }

    public function testRejectsMalformedPreviewState(): void
    {
        $manager = new SubmissionDeletionPreview(900);

        $this->assertFalse($manager->isValid([], 'invalid', self::THRESHOLD, self::NOW));

        $preview = $manager->create([10], self::THRESHOLD, self::NOW);
        $preview['submissionIds'] = ['invalid'];
        $this->assertFalse($manager->isValid(
            $preview,
            $preview['id'],
            self::THRESHOLD,
            self::NOW
        ));
    }
}
