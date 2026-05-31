<?php

namespace App\Support;

use App\Enums\TaskKind;
use App\Enums\TaskStatus;
use App\Models\OrganizationMember;
use App\Models\Task;

final class CreateFollowUpReminder
{
    public function createForTask(Task $task, OrganizationMember $actor): ?Task
    {
        if ($task->kind !== TaskKind::Task) {
            return null;
        }

        $existing = Task::query()
            ->forOrganization($task->organization_id)
            ->ofKind(TaskKind::Reminder)
            ->where('meta->source_task_id', $task->id)
            ->exists();

        if ($existing) {
            return null;
        }

        $reminder = Task::query()->create([
            'organization_id' => $task->organization_id,
            'project_id' => $task->project_id,
            'kind' => TaskKind::Reminder,
            'title' => __('Follow-up: :title', ['title' => $task->title]),
            'description' => $task->description,
            'created_by_member_id' => $actor->id,
            'status' => TaskStatus::Pending,
            'meta' => [
                'source_task_id' => $task->id,
                'icon' => '🔔',
            ],
        ]);

        $task->loadMissing('assignees');

        if ($task->assignees->isNotEmpty()) {
            $syncPayload = [];

            foreach ($task->assignees as $index => $assignee) {
                $syncPayload[$assignee->id] = [
                    'is_primary' => $index === 0,
                    'assigned_at' => now(),
                    'assigned_by_member_id' => $actor->id,
                ];
            }

            $reminder->assignees()->sync($syncPayload);
        }

        return $reminder;
    }
}
