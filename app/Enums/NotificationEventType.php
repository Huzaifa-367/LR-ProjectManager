<?php

namespace App\Enums;

enum NotificationEventType: string
{
    case MemberInvited = 'member_invited';
    case MemberJoined = 'member_joined';
    case TaskAssigned = 'task_assigned';
    case TaskDueSoon = 'task_due_soon';
    case TaskOverdue = 'task_overdue';
    case TaskStatusChanged = 'task_status_changed';
    case TaskCommentAdded = 'task_comment_added';
    case FocusReminder = 'focus_reminder';
    case DailyDigest = 'daily_digest';
    case ProjectArchived = 'project_archived';

    public function label(): string
    {
        return match ($this) {
            self::MemberInvited => 'Member invited',
            self::MemberJoined => 'Member joined',
            self::TaskAssigned => 'Task assigned',
            self::TaskDueSoon => 'Task due soon',
            self::TaskOverdue => 'Task overdue',
            self::TaskStatusChanged => 'Task status changed',
            self::TaskCommentAdded => 'Task comment added',
            self::FocusReminder => 'Focus reminder',
            self::DailyDigest => 'Daily digest',
            self::ProjectArchived => 'Project archived',
        };
    }
}
