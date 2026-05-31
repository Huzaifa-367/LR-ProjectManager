<?php

namespace App\Support;

use App\Enums\NotificationChannel;
use App\Enums\NotificationEventType;
use App\Mail\TaskAssignedMail;
use App\Mail\TaskDueSoonMail;
use App\Models\MemberNotificationPreference;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\ScheduledNotification;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskDueSoonNotification;
use Illuminate\Database\Eloquent\Model;

final class NotificationDispatcher
{
    public function __construct(
        private readonly OrganizationMailDeliveryService $mailDeliveryService,
    ) {}

    public function dispatchTaskAssigned(
        Task $task,
        OrganizationMember $recipient,
        ?OrganizationMember $assignedBy = null,
    ): void {
        $task->loadMissing(['organization', 'project']);

        $this->dispatch(
            organization: $task->organization,
            recipient: $recipient,
            eventType: NotificationEventType::TaskAssigned,
            subject: $task,
            payload: [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'project_name' => $task->project?->name,
                'assigned_by' => $assignedBy?->display_name,
            ],
            databaseNotification: new TaskAssignedNotification($task, $assignedBy),
            mailable: new TaskAssignedMail($task, $assignedBy),
        );
    }

    public function dispatchScheduled(ScheduledNotification $scheduled): void
    {
        $scheduled->loadMissing(['organization', 'organizationMember', 'subject']);

        if (! $scheduled->subject instanceof Task) {
            return;
        }

        if ($scheduled->event_type !== NotificationEventType::TaskDueSoon) {
            return;
        }

        $this->dispatchTaskDueSoon($scheduled->subject, $scheduled->organizationMember);
    }

    public function dispatchTaskDueSoon(Task $task, OrganizationMember $recipient): void
    {
        $task->loadMissing(['organization', 'project']);

        if ($task->is_done || $task->deadline_date === null) {
            return;
        }

        $this->dispatch(
            organization: $task->organization,
            recipient: $recipient,
            eventType: NotificationEventType::TaskDueSoon,
            subject: $task,
            payload: [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'deadline_date' => $task->deadline_date->toIso8601String(),
                'project_name' => $task->project?->name,
            ],
            databaseNotification: new TaskDueSoonNotification($task),
            mailable: new TaskDueSoonMail($task),
        );
    }

    public function isEnabled(
        OrganizationMember $recipient,
        NotificationEventType $eventType,
        NotificationChannel $channel,
    ): bool {
        $preference = MemberNotificationPreference::query()
            ->where('organization_member_id', $recipient->id)
            ->where('event_type', $eventType->value)
            ->where('channel', $channel->value)
            ->first();

        if ($preference === null) {
            return true;
        }

        return $preference->is_enabled;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function dispatch(
        Organization $organization,
        OrganizationMember $recipient,
        NotificationEventType $eventType,
        Model $subject,
        array $payload,
        TaskAssignedNotification|TaskDueSoonNotification $databaseNotification,
        TaskAssignedMail|TaskDueSoonMail $mailable,
    ): void {
        if ($recipient->user_id === null) {
            return;
        }

        $user = User::query()->find($recipient->user_id);

        if ($user === null) {
            return;
        }

        if ($this->isEnabled($recipient, $eventType, NotificationChannel::Database)) {
            $user->notify($databaseNotification);
        }

        if ($this->isEnabled($recipient, $eventType, NotificationChannel::Mail)) {
            $this->mailDeliveryService->send(
                organization: $organization,
                recipient: $recipient,
                eventType: $eventType,
                subject: $subject,
                mailable: $mailable,
                mailSubject: $mailable->envelope()->subject,
            );
        }
    }
}
