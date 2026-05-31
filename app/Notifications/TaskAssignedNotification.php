<?php

namespace App\Notifications;

use App\Enums\NotificationEventType;
use App\Models\OrganizationMember;
use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Task $task,
        private readonly ?OrganizationMember $assignedBy = null,
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
        $assignedByName = $this->assignedBy?->display_name;

        return [
            'title' => __('Task assigned'),
            'body' => $assignedByName !== null
                ? __(':name assigned you to :task.', [
                    'name' => $assignedByName,
                    'task' => $this->task->title,
                ])
                : __('You were assigned to :task.', ['task' => $this->task->title]),
            'action_url' => route('organizations.tasks.show', [$organization, $this->task]),
            'organization_id' => $organization->id,
            'task_id' => $this->task->id,
            'event_type' => NotificationEventType::TaskAssigned->value,
        ];
    }
}
