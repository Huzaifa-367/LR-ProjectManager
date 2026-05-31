<?php

namespace Tests\Unit;

use App\Support\OnboardingBriefParser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OnboardingBriefParserTest extends TestCase
{
    #[Test]
    public function it_extracts_structure_from_a_structured_brief(): void
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

        $structure = app(OnboardingBriefParser::class)->extractStructure($brief);

        $this->assertSame('TAP Global Expansion', $structure['project_name']);
        $this->assertStringContainsString('Launch TAP in 3 new markets by Q4', (string) $structure['objective']);
        $this->assertGreaterThanOrEqual(3, $structure['work_item_count']);
        $this->assertTrue($structure['has_timeline']);
        $this->assertTrue($structure['has_success_criteria']);
        $this->assertTrue($structure['has_decisions']);
    }

    #[Test]
    public function it_condenses_long_unlabeled_first_lines_into_short_project_names(): void
    {
        $brief = <<<'TXT'
Launch TAP platform expansion across three new international markets by Q4

Scope:
- Market research
TXT;

        $structure = app(OnboardingBriefParser::class)->extractStructure($brief);

        $this->assertLessThanOrEqual(55, mb_strlen((string) $structure['project_name']));
        $this->assertStringNotContainsString('by Q4', (string) $structure['project_name']);
    }

    #[Test]
    public function it_returns_empty_structure_for_blank_briefs(): void
    {
        $structure = app(OnboardingBriefParser::class)->extractStructure('   ');

        $this->assertNull($structure['project_name']);
        $this->assertSame(0, $structure['work_item_count']);
        $this->assertFalse($structure['has_timeline']);
    }
}
