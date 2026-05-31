<?php

namespace App\Observers;

use App\Models\Task;
use App\Support\SyncProjectProgress;

class TaskObserver
{
    public function saved(Task $task): void
    {
        if ($task->project_id === null) {
            return;
        }

        app(SyncProjectProgress::class)->syncForProjectId($task->project_id);
    }

    public function deleted(Task $task): void
    {
        if ($task->project_id === null) {
            return;
        }

        app(SyncProjectProgress::class)->syncForProjectId($task->project_id);
    }
}
