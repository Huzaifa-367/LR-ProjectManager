<?php

namespace Tests\Unit;

use App\Support\OnboardingPlanGenerationException;
use App\Support\OnboardingPlanGenerator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OnboardingPlanGeneratorTest extends TestCase
{
    #[Test]
    public function it_returns_ai_plan_without_trimming_titles(): void
    {
        $this->fakeOnboardingPlan([
            'project_name' => 'Command Centre MVP',
            'objective' => 'Deliver the executive command centre described in the brief.',
            'next_action' => 'Align stakeholders on scope',
            'tasks' => [
                [
                    'title' => 'Establish authentication and organization foundation',
                    'description' => 'Objective: Ship org-aware auth flows.',
                    'priority' => 'high',
                    'deadline_date' => null,
                ],
                [
                    'title' => 'Implement unified work management',
                    'description' => 'Objective: Deliver tasks, decisions, and reminders.',
                    'priority' => 'high',
                    'deadline_date' => null,
                ],
            ],
            'decisions' => [
                ['title' => 'Approve project charter'],
            ],
            'reminders' => [
                [
                    'title' => 'Weekly status sync',
                    'description' => 'Review blockers and next actions.',
                ],
            ],
        ]);

        $plan = app(OnboardingPlanGenerator::class)->generate('Any brief', 1, 'software');

        $this->assertSame('Command Centre MVP', $plan['project_name']);
        $this->assertSame(
            'Establish authentication and organization foundation',
            $plan['tasks'][0]['title'],
        );
        $this->assertStringNotContainsString('...', $plan['tasks'][0]['title']);
    }

    #[Test]
    public function it_throws_when_ai_is_not_configured(): void
    {
        config(['ai.providers.openai.key' => null]);

        $this->expectException(OnboardingPlanGenerationException::class);

        app(OnboardingPlanGenerator::class)->generate('Brief', 1);
    }
}
