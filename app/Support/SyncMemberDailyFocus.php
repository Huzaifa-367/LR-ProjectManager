<?php

namespace App\Support;

use App\Enums\DeadlineType;
use App\Enums\TaskKind;
use App\Models\MemberDailyFocus;
use App\Models\Task;
use Carbon\Carbon;

final class SyncMemberDailyFocus
{
    public function syncForTask(Task $task, ?Carbon $focusDate = null): void
    {
        if ($task->kind !== TaskKind::Task) {
            return;
        }

        $focusDate ??= now()->startOfDay();

        if ($task->is_done || $task->deadline_type !== DeadlineType::Today) {
            MemberDailyFocus::query()
                ->where('task_id', $task->id)
                ->where('is_auto', true)
                ->delete();

            return;
        }

        $task->loadMissing('assignees');

        foreach ($task->assignees as $assignee) {
            MemberDailyFocus::query()->firstOrCreate(
                [
                    'organization_member_id' => $assignee->id,
                    'task_id' => $task->id,
                    'focus_date' => $focusDate->toDateString(),
                ],
                [
                    'sort_order' => (int) MemberDailyFocus::query()
                        ->where('organization_member_id', $assignee->id)
                        ->whereDate('focus_date', $focusDate)
                        ->max('sort_order') + 1,
                    'is_auto' => true,
                ],
            );
        }
    }
}
