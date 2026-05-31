<?php

namespace App\Support;

use App\Enums\TaskKind;
use App\Models\Project;
use App\Models\Task;

final class SyncProjectProgress
{
    public function syncForProjectId(int $projectId): void
    {
        $project = Project::query()->find($projectId);

        if ($project === null) {
            return;
        }

        $this->sync($project);
    }

    public function sync(Project $project): void
    {
        $tasks = Task::query()
            ->where('project_id', $project->id)
            ->where('kind', TaskKind::Task)
            ->get(['id', 'title', 'is_done', 'sort_order']);

        $total = $tasks->count();
        $done = $tasks->where('is_done', true)->count();

        $progressPercent = $total === 0 ? 0 : (int) round($done / $total * 100);

        $nextAction = $tasks
            ->where('is_done', false)
            ->sortBy([
                ['sort_order', 'asc'],
                ['id', 'asc'],
            ])
            ->first()
            ?->title;

        $project->updateQuietly([
            'progress_percent' => $progressPercent,
            'next_action' => $nextAction,
        ]);
    }
}
