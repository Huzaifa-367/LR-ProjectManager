<?php

namespace App\Support;

use App\Models\Organization;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

final class OnboardingRateLimiter
{
    public static function attemptPropose(Organization $organization): void
    {
        $key = 'ai-onboarding-propose:org:'.$organization->id;
        $maxAttempts = (int) config('onboarding.rate_limits.propose_per_hour', 30);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            throw new TooManyRequestsHttpException(
                $seconds,
                __('Too many plan generation attempts. Please wait :minutes minute(s) and try again.', [
                    'minutes' => (int) ceil($seconds / 60),
                ]),
            );
        }

        RateLimiter::hit($key, decaySeconds: 3600);
    }

    public static function attemptApply(Organization $organization): void
    {
        $key = 'ai-onboarding-apply:org:'.$organization->id;
        $maxAttempts = (int) config('onboarding.rate_limits.apply_per_hour', 10);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            throw new TooManyRequestsHttpException(
                $seconds,
                __('Too many apply attempts. Please wait :minutes minute(s) and try again.', [
                    'minutes' => (int) ceil($seconds / 60),
                ]),
            );
        }

        RateLimiter::hit($key, decaySeconds: 3600);
    }
}
