<?php

namespace Tests\Unit;

use App\Support\OnboardingBriefAnalyzer;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OnboardingBriefAnalyzerTest extends TestCase
{
    #[Test]
    public function it_marks_sparse_briefs_incomplete_and_lists_required_questions(): void
    {
        $assessment = app(OnboardingBriefAnalyzer::class)->assess(
            'Launch TAP in three new markets by Q4',
            1,
            true,
        );

        $this->assertFalse($assessment['is_complete']);
        $this->assertSame('follow_up', $assessment['phase']);
        $this->assertContains('work_items', $assessment['missing_required']);
        $this->assertNotEmpty($assessment['questions']);
        $this->assertArrayHasKey('project_profile', $assessment);

        $workItemsQuestion = collect($assessment['questions'])
            ->firstWhere('key', 'work_items');

        $this->assertNotNull($workItemsQuestion);
        $this->assertSame('list', $workItemsQuestion['input_mode']);
        $this->assertNotEmpty($workItemsQuestion['suggestions']);
    }

    #[Test]
    public function it_detects_training_profile_and_adds_dynamic_questions(): void
    {
        $brief = <<<'TXT'
Project: Leadership fundamentals course
Objective: Build a two-day leadership training for new managers with certification.
Timeline: Pilot in Q2, full rollout in Q3
TXT;

        $assessment = app(OnboardingBriefAnalyzer::class)->assess($brief, 1, true);

        $this->assertSame('training', $assessment['project_profile']['key']);
        $this->assertFalse($assessment['is_complete']);

        $questionKeys = array_column($assessment['questions'], 'key');

        $this->assertContains('work_items', $questionKeys);
        $this->assertTrue(
            count(array_filter($questionKeys, fn (string $key): bool => str_starts_with($key, 'dynamic_'))) >= 1,
        );
    }

    #[Test]
    public function it_starts_in_initial_phase_when_no_brief_submitted(): void
    {
        $assessment = app(OnboardingBriefAnalyzer::class)->assess('', 0, false);

        $this->assertSame('initial', $assessment['phase']);
        $this->assertSame('general', $assessment['project_profile']['key']);
    }

    #[Test]
    public function it_marks_structured_briefs_complete(): void
    {
        $brief = <<<'TXT'
Project: Vendor Portal

Objective: Replace legacy vendor onboarding with a secure self-service portal.

Scope:
- Integrate SSO with identity provider
- Migrate legacy vendor records

Timeline:
- Q2: SSO integration
TXT;

        $assessment = app(OnboardingBriefAnalyzer::class)->assess($brief, 1, true);

        $this->assertTrue($assessment['is_complete']);
        $this->assertSame('ready', $assessment['phase']);
        $this->assertSame('software', $assessment['project_profile']['key']);
        $this->assertSame([], $assessment['missing_required']);
    }
}
