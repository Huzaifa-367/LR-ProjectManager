<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Audit retention (days)
    |--------------------------------------------------------------------------
    |
    | Stale rows are removed by scheduled artisan commands. Per-org overrides
    | may be added to organization settings later.
    |
    */

    'retention' => [
        'ai_audit_logs_days' => (int) env('COMMAND_CENTRE_AI_AUDIT_RETENTION_DAYS', 90),
        'activity_logs_days' => (int) env('COMMAND_CENTRE_ACTIVITY_LOG_RETENTION_DAYS', 365),
    ],

];
