<?php

namespace Tests;

use App\Ai\Agents\ProjectOnboardingPlanAgent;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['onboarding.ai.enabled' => true]);
        $this->fakeOnboardingPlan();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function fakeOnboardingPlan(array $overrides = []): void
    {
        ProjectOnboardingPlanAgent::fake([
            array_merge([
                'project_name' => 'TAP Global Expansion',
                'objective' => 'Launch in three new markets by Q4 with measurable adoption targets.',
                'next_action' => 'Complete market research for target regions',
                'tasks' => [
                    [
                        'title' => 'Research target regions',
                        'description' => "Objective: Validate market entry assumptions.\nSteps:\n- Interview regional stakeholders\n- Document regulatory constraints\nAcceptance: Research summary approved.",
                        'priority' => 'high',
                        'deadline_date' => now()->endOfWeek()->toDateString(),
                    ],
                    [
                        'title' => 'Onboard regional vendors',
                        'description' => "Objective: Establish vendor pipeline.\nSteps:\n- Shortlist vendors\n- Complete legal review\nAcceptance: Vendor contracts signed.",
                        'priority' => 'medium',
                        'deadline_date' => null,
                    ],
                ],
                'decisions' => [
                    ['title' => 'Approve initial market entry order'],
                ],
                'reminders' => [
                    [
                        'title' => 'Weekly steering sync',
                        'description' => 'Review rollout progress, blockers, and next actions.',
                    ],
                ],
            ], $overrides),
        ]);
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
