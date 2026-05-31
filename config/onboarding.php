<?php

return [

    'ai' => [
        'enabled' => env('AI_ONBOARDING_ENABLED', true),
        'model' => env('AI_ONBOARDING_MODEL', 'gpt-4o-mini'),
        'timeout' => (int) env('AI_ONBOARDING_TIMEOUT', 120),
    ],

    'max_tasks' => (int) env('AI_ONBOARDING_MAX_TASKS', 15),

    'rate_limits' => [
        'propose_per_hour' => (int) env('AI_ONBOARDING_PROPOSE_PER_HOUR', 30),
        'apply_per_hour' => (int) env('AI_ONBOARDING_APPLY_PER_HOUR', 10),
    ],

];
