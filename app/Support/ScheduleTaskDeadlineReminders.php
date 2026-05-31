<?php

namespace App\Support;

use App\Enums\NotificationChannel;
use App\Enums\NotificationEventType;
use App\Enums\ScheduledNotificationStatus;
use App\Models\ScheduledNotification;
use App\Models\Task;
use Carbon\CarbonImmutable;

final class ScheduleTaskDeadlineReminders
{
    public function syncForTask(Task $task): void
    {
        $task->loadMissing(['assignees', 'organization']);

        if ($task->is_done || $task->deadline_date === null) {
            $this->cancelForTask($task);

            return;
        }

        $organization = $task->organization;
        $settings = $organization->settings ?? [];
        $notificationSettings = $settings['notifications'] ?? Organization::defaultSettings()['notifications'];
        $timezone = (string) ($settings['timezone'] ?? config('app.timezone', 'UTC'));
        $reminderDays = $notificationSettings['task_due_reminder_days'] ?? [1, 0];
        $reminderTime = (string) ($notificationSettings['task_due_reminder_time'] ?? '08:00');

        $dueAt = CarbonImmutable::parse($task->deadline_date)->timezone($timezone);

        /** @var list<string> $validDedupeKeys */
        $validDedupeKeys = [];

        foreach ($task->assignees as $assignee) {
            foreach ($reminderDays as $offset) {
                $triggerAt = (int) $offset === 0
                    ? $dueAt
                    : $dueAt->startOfDay()
                        ->subDays((int) $offset)
                        ->setTimeFromTimeString($reminderTime);

                $dedupeKey = sprintf(
                    'task:%d:due_soon:member:%d:%s',
                    $task->id,
                    $assignee->id,
                    $triggerAt->format('Y-m-d'),
                );

                $validDedupeKeys[] = $dedupeKey;

                ScheduledNotification::query()->updateOrCreate(
                    ['dedupe_key' => $dedupeKey],
                    [
                        'organization_id' => $organization->id,
                        'organization_member_id' => $assignee->id,
                        'event_type' => NotificationEventType::TaskDueSoon,
                        'channel' => NotificationChannel::Mail,
                        'subject_type' => Task::class,
                        'subject_id' => $task->id,
                        'trigger_at' => $triggerAt,
                        'payload' => [
                            'task_id' => $task->id,
                            'task_title' => $task->title,
                            'deadline_date' => $task->deadline_date->toIso8601String(),
                        ],
                        'status' => ScheduledNotificationStatus::Pending,
                        'cancelled_at' => null,
                    ],
                );
            }
        }

        ScheduledNotification::query()
            ->where('subject_type', Task::class)
            ->where('subject_id', $task->id)
            ->where('event_type', NotificationEventType::TaskDueSoon)
            ->where('status', ScheduledNotificationStatus::Pending)
            ->whereNotIn('dedupe_key', $validDedupeKeys)
            ->update([
                'status' => ScheduledNotificationStatus::Cancelled,
                'cancelled_at' => now(),
            ]);
    }

    public function cancelForTask(Task $task): void
    {
        ScheduledNotification::query()
            ->where('subject_type', Task::class)
            ->where('subject_id', $task->id)
            ->where('status', ScheduledNotificationStatus::Pending)
            ->update([
                'status' => ScheduledNotificationStatus::Cancelled,
                'cancelled_at' => now(),
            ]);
    }
}
