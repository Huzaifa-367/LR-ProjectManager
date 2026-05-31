<?php

namespace App\Support;

use RuntimeException;

final class OnboardingPlanGenerationException extends RuntimeException
{
    public static function disabled(): self
    {
        return new self(__('AI onboarding plan generation is disabled.'));
    }

    public static function notConfigured(): self
    {
        return new self(__('AI is not configured. Add an API key for your AI provider to generate project plans.'));
    }

    public static function failed(): self
    {
        return new self(__('The AI could not generate a valid project plan. Try adding more detail to your brief and try again.'));
    }

    public static function rateLimited(int $retryAfterSeconds = 60): self
    {
        return new self(__('The AI provider is rate limited. Please wait :minutes minute(s) and try again.', [
            'minutes' => max(1, (int) ceil($retryAfterSeconds / 60)),
        ]));
    }
}
