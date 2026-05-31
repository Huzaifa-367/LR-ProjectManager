<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;

class PurgeStaleActivityLogsCommand extends Command
{
    protected $signature = 'audit:purge-activity-logs';

    protected $description = 'Delete activity log rows older than the configured retention period';

    public function handle(): int
    {
        $days = (int) config('command_centre.retention.activity_logs_days', 365);
        $cutoff = now()->subDays($days);

        $deleted = ActivityLog::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("Purged {$deleted} activity log row(s) older than {$days} days.");

        return self::SUCCESS;
    }
}
