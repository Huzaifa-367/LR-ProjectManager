<?php

namespace App\Support;

use App\Enums\TaskStatus;
use App\Models\OrganizationMember;
use App\Models\Task;

final class NormalizeTaskCompletion
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function mergeForUpdate(Task $task, OrganizationMember $member, array $attributes): array
    {
        if (! array_key_exists('status', $attributes)) {
            return $attributes;
        }

        $status = $attributes['status'] instanceof TaskStatus
            ? $attributes['status']
            : TaskStatus::from((string) $attributes['status']);

        return array_merge($attributes, $this->completionFieldsForStatus($task, $member, $status));
    }

    /**
     * @return array<string, mixed>
     */
    public function forStatusChange(Task $task, OrganizationMember $member, TaskStatus $status): array
    {
        return array_merge(
            ['status' => $status],
            $this->completionFieldsForStatus($task, $member, $status),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function forToggleDone(Task $task, OrganizationMember $member, bool $isDone): array
    {
        if ($isDone) {
            return [
                'is_done' => true,
                'completed_at' => now(),
                'completed_by_member_id' => $member->id,
                'status' => TaskStatus::Done,
            ];
        }

        return [
            'is_done' => false,
            'completed_at' => null,
            'completed_by_member_id' => null,
            'status' => TaskStatus::Pending,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function completionFieldsForStatus(
        Task $task,
        OrganizationMember $member,
        TaskStatus $status,
    ): array {
        if ($status === TaskStatus::Done) {
            return [
                'is_done' => true,
                'completed_at' => $task->completed_at ?? now(),
                'completed_by_member_id' => $task->completed_by_member_id ?? $member->id,
            ];
        }

        if ($task->is_done || $task->status === TaskStatus::Done) {
            return [
                'is_done' => false,
                'completed_at' => null,
                'completed_by_member_id' => null,
            ];
        }

        return [];
    }
}
