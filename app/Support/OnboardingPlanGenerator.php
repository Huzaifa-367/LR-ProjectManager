<?php

namespace App\Support;

final class OnboardingPlanGenerator
{
    public function __construct(
        private readonly OnboardingPlanAiGenerator $aiGenerator,
    ) {}

    /**
     * @return array{
     *     project_name: string,
     *     objective: string,
     *     next_action: string,
     *     tasks: list<array<string, mixed>>,
     *     decisions: list<array<string, mixed>>,
     *     reminders: list<array<string, mixed>>
     * }
     */
    public function generate(string $brief, int $leadMemberId, string $profileKey = 'general'): array
    {
        if (! config('onboarding.ai.enabled', true)) {
            throw OnboardingPlanGenerationException::disabled();
        }

        if (! $this->aiGenerator->isConfigured()) {
            throw OnboardingPlanGenerationException::notConfigured();
        }

        $plan = $this->aiGenerator->generate($brief, $leadMemberId, $profileKey);

        if ($plan === null) {
            throw OnboardingPlanGenerationException::failed();
        }

        return $plan;
    }
}
