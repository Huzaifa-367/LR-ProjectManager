<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('notifications:dispatch-scheduled')->everyMinute();
Schedule::command('exports:purge-expired')->daily();
Schedule::command('audit:purge-ai-logs')->daily();
Schedule::command('audit:purge-activity-logs')->weekly();
