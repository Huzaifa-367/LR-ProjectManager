<?php

namespace Tests\Unit;

use App\Support\OnboardingBriefParser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OnboardingBriefParserTest extends TestCase
{
    #[Test]
    public function it_parses_structured_brief_into_multiple_tasks_and_decisions(): void
    {
        $brief = <<<'TXT'
Project: TAP Global Expansion

Objective: Launch TAP in 3 new markets by Q4

Scope:
- Complete market research for Centrum and MENA
- Vendor onboarding and legal review
- Phased rollout plan

Success criteria:
- 3 markets live by December

Timeline:
- Q3: Research and vendor selection
- Q4: Rollout

Decisions needed:
- Approve initial budget envelope
- Select primary market entry order
TXT;

        $plan = app(OnboardingBriefParser::class)->parse($brief, 42);

        $this->assertSame('TAP Global Expansion', $plan['project_name']);
        $this->assertStringContainsString('Launch TAP in 3 new markets by Q4', $plan['objective']);
        $this->assertGreaterThanOrEqual(5, count($plan['tasks']));
        $this->assertGreaterThanOrEqual(2, count($plan['decisions']));
        $this->assertNotEmpty($plan['reminders']);
        $this->assertLessThanOrEqual(58, mb_strlen($plan['tasks'][0]['title']));
        $this->assertStringContainsString('Work:', $plan['tasks'][0]['description']);
        $this->assertSame([42], $plan['tasks'][0]['assignee_member_ids']);
        $this->assertSame('high', $plan['tasks'][0]['priority']);
    }

    #[Test]
    public function it_condenses_long_unlabeled_first_lines_into_short_project_names(): void
    {
        $brief = <<<'TXT'
Launch TAP platform expansion across three new international markets by Q4

Scope:
- Market research
TXT;

        $plan = app(OnboardingBriefParser::class)->parse($brief, 1);

        $this->assertLessThanOrEqual(55, mb_strlen($plan['project_name']));
        $this->assertStringNotContainsString('by Q4', $plan['project_name']);
    }

    #[Test]
    public function it_adds_staple_tasks_for_sparse_briefs(): void
    {
        $plan = app(OnboardingBriefParser::class)->parse('Launch something important', 1);

        $this->assertGreaterThanOrEqual(3, count($plan['tasks']));
        $this->assertNotSame('', $plan['project_name']);
    }
}
