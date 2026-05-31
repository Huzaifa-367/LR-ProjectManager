<?php

namespace App\Notifications;

use App\Enums\NotificationEventType;
use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskDueSoonNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Task $task,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $organization = $this->task->organization;
        $deadline = $this->task->deadline_date?->format('M j, Y g:i A');

        return [
            'title' => __('Task due soon'),
            'body' => $deadline !== null
                ? __(':task is due on :date.', [
                    'task' => $this->task->title,
                    'date' => $deadline,
                ])
                : __(':task is due soon.', ['task' => $this->task->title]),
            'action_url' => route('organizations.tasks.show', [$organization, $this->task]),
            'organization_id' => $organization->id,
            'task_id' => $this->task->id,
            'event_type' => NotificationEventType::TaskDueSoon->value,
        ];
    }
}
