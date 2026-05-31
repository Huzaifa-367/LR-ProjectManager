<?php

namespace App\Jobs;

use App\Enums\ExportJobStatus;
use App\Enums\ExportType;
use App\Models\ExportJob;
use App\Support\TaskCsvExporter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessExportJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $exportJobId,
    ) {}

    public function handle(TaskCsvExporter $taskCsvExporter): void
    {
        $exportJob = ExportJob::query()
            ->with('requestedByMember')
            ->find($this->exportJobId);

        if ($exportJob === null) {
            return;
        }

        $exportJob->update(['status' => ExportJobStatus::Processing]);

        try {
            $path = match ($exportJob->export_type) {
                ExportType::TasksCsv => $taskCsvExporter->export(
                    $exportJob,
                    $exportJob->requestedByMember,
                ),
                default => throw new \InvalidArgumentException(
                    __('Export type :type is not supported yet.', [
                        'type' => $exportJob->export_type->value,
                    ]),
                ),
            };

            $exportJob->update([
                'status' => ExportJobStatus::Completed,
                'path' => $path,
                'completed_at' => now(),
                'error_message' => null,
            ]);
        } catch (Throwable $exception) {
            $exportJob->update([
                'status' => ExportJobStatus::Failed,
                'error_message' => $exception->getMessage(),
            ]);

            report($exception);
        }
    }
}
