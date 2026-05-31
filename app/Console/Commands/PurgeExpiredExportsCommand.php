<?php

namespace App\Console\Commands;

use App\Models\ExportJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeExpiredExportsCommand extends Command
{
    protected $signature = 'exports:purge-expired';

    protected $description = 'Delete expired export files and job rows';

    public function handle(): int
    {
        $expired = ExportJob::query()
            ->where('expires_at', '<=', now())
            ->get();

        foreach ($expired as $exportJob) {
            if ($exportJob->path !== null) {
                Storage::disk($exportJob->disk)->delete($exportJob->path);
            }

            $exportJob->delete();
        }

        return self::SUCCESS;
    }
}
