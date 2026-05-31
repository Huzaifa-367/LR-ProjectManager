<?php

namespace App\Support;

use App\Enums\ExportType;
use App\Enums\TaskKind;
use App\Models\ExportJob;
use App\Models\OrganizationMember;
use App\Models\Task;
use Illuminate\Support\Facades\Storage;

final class TaskCsvExporter
{
    public function __construct(
        private readonly TaskVisibilityScope $visibilityScope,
    ) {}

    public function export(ExportJob $exportJob, OrganizationMember $member): string
    {
        abort_unless($exportJob->export_type === ExportType::TasksCsv, 422);

        $query = Task::query()
            ->with(['project', 'assignees'])
            ->tap(fn ($builder) => $this->visibilityScope->apply($builder, $member));

        $filters = $exportJob->filters ?? [];

        if (! empty($filters['project_id'])) {
            $query->where('project_id', (int) $filters['project_id']);
        }

        if (! empty($filters['kind'])) {
            $kind = TaskKind::tryFrom((string) $filters['kind']);

            if ($kind !== null) {
                $query->ofKind($kind);
            }
        }

        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            throw new \RuntimeException('Unable to open temporary export stream.');
        }

        fputcsv($handle, [
            'id',
            'project',
            'kind',
            'title',
            'status',
            'priority',
            'deadline_date',
            'is_done',
            'assignees',
            'created_at',
            'updated_at',
        ]);

        $query->orderBy('id')->chunk(200, function ($tasks) use ($handle): void {
            foreach ($tasks as $task) {
                /** @var Task $task */
                fputcsv($handle, [
                    $task->id,
                    $task->project?->name,
                    $task->kind->value,
                    $task->title,
                    $task->status->value,
                    $task->priority?->value,
                    $task->deadline_date?->format('Y-m-d'),
                    $task->is_done ? '1' : '0',
                    $task->assignees->pluck('display_name')->filter()->implode('; '),
                    $task->created_at?->toIso8601String(),
                    $task->updated_at?->toIso8601String(),
                ]);
            }
        });

        rewind($handle);
        $contents = stream_get_contents($handle);
        fclose($handle);

        $path = sprintf(
            'exports/organization-%d/export-%d-tasks.csv',
            $exportJob->organization_id,
            $exportJob->id,
        );

        Storage::disk($exportJob->disk)->put($path, $contents ?: '');

        return $path;
    }
}
