<?php

namespace App\Console\Commands;

use App\Models\AiAuditLog;
use Illuminate\Console\Command;

class PurgeStaleAiAuditLogsCommand extends Command
{
    protected $signature = 'audit:purge-ai-logs';

    protected $description = 'Delete AI audit log rows older than the configured retention period';

    public function handle(): int
    {
        $days = (int) config('command_centre.retention.ai_audit_logs_days', 90);
        $cutoff = now()->subDays($days);

        $deleted = AiAuditLog::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("Purged {$deleted} AI audit log row(s) older than {$days} days.");

        return self::SUCCESS;
    }
}
