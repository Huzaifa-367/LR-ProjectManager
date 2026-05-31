<?php

namespace App\Support;

use App\Models\AiOnboardingProposal;
use App\Models\AiSession;
use App\Models\Attachment;
use App\Models\MemberDailyFocus;
use App\Models\MemberNote;
use App\Models\OrganizationMember;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use Carbon\Carbon;

final class CommandCentreResourcePresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function project(Project $project): array
    {
        return [
            'id' => $project->id,
            'name' => $project->name,
            'objective' => $project->objective,
            'progress_percent' => $project->progress_percent,
            'next_action' => $project->next_action,
            'health' => $project->health->value,
            'archived_at' => $project->archived_at?->toIso8601String(),
            'owner_member_id' => $project->owner_member_id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function task(Task $task): array
    {
        $task->loadMissing(['project', 'assignees.user']);

        return [
            'id' => $task->id,
            'kind' => $task->kind->value,
            'project_id' => $task->project_id,
            'project_name' => $task->project?->name,
            'title' => $task->title,
            'description' => $task->description,
            'priority' => $task->priority?->value,
            'status' => $task->status->value,
            'deadline_date' => $task->deadline_date?->toIso8601String(),
            'external_link' => $task->external_link,
            'is_done' => $task->is_done,
            'created_by_member_id' => $task->created_by_member_id,
            'assignees' => $task->assignees->map(fn (OrganizationMember $member): array => [
                'id' => $member->id,
                'display_name' => $member->display_name,
                'is_primary' => (bool) $member->pivot?->is_primary,
            ])->values()->all(),
        ];
    }

    /**
     * @return array{org: list<string>, projects: array<int, list<string>>}
     */
    public static function permissions(
        EffectivePermissionService $permissions,
        OrganizationMember $member,
        ?Project $project = null,
    ): array {
        return $permissions->sharedPermissionsForMember($member, $project);
    }

    /**
     * @return array<string, mixed>
     */
    public static function focusPin(MemberDailyFocus $pin): array
    {
        $pin->loadMissing(['task.project', 'task.assignees']);

        return [
            'id' => $pin->id,
            'sort_order' => $pin->sort_order,
            'is_auto' => $pin->is_auto,
            'task' => self::commandCentreTask($pin->task),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function commandCentreTask(Task $task): array
    {
        $task->loadMissing(['project', 'assignees']);

        return [
            'id' => $task->id,
            'kind' => $task->kind->value,
            'project_id' => $task->project_id,
            'project_name' => $task->project?->name,
            'title' => $task->title,
            'description' => $task->description,
            'priority' => $task->priority?->value,
            'status' => $task->status->value,
            'deadline_date' => $task->deadline_date?->toIso8601String(),
            'external_link' => $task->external_link,
            'is_done' => $task->is_done,
            'meta' => $task->meta,
            'deadline_ui' => self::deadlineUi($task),
            'assignees' => $task->assignees->map(fn (OrganizationMember $member): array => [
                'id' => $member->id,
                'display_name' => $member->display_name,
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function commandCentreProject(Project $project): array
    {
        return [
            'id' => $project->id,
            'name' => $project->name,
            'objective' => $project->objective,
            'progress_percent' => $project->progress_percent,
            'next_action' => $project->next_action,
            'health' => $project->health->value,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function memberNote(MemberNote $note): array
    {
        return [
            'id' => $note->id,
            'body' => $note->body,
            'sort_order' => $note->sort_order,
            'updated_at' => $note->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function aiSession(AiSession $session): array
    {
        return [
            'id' => $session->id,
            'context' => $session->context->value,
            'title' => $session->title,
            'status' => $session->status->value,
            'project_id' => $session->project_id,
            'last_message_at' => $session->last_message_at?->toIso8601String(),
            'created_at' => $session->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function onboardingProposal(AiOnboardingProposal $proposal): array
    {
        return [
            'id' => $proposal->id,
            'ai_session_id' => $proposal->ai_session_id,
            'proposal_type' => $proposal->proposal_type->value,
            'status' => $proposal->status->value,
            'payload' => $proposal->payload,
            'summary' => $proposal->summary,
            'project_id' => $proposal->project_id,
            'version' => $proposal->version,
            'applied_at' => $proposal->applied_at?->toIso8601String(),
            'rejection_reason' => $proposal->rejection_reason,
            'created_at' => $proposal->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function taskComment(TaskComment $comment): array
    {
        $comment->loadMissing('author');

        return [
            'id' => $comment->id,
            'body' => $comment->body,
            'parent_id' => $comment->parent_id,
            'author' => [
                'id' => $comment->author?->id,
                'display_name' => $comment->author?->display_name,
            ],
            'edited_at' => $comment->edited_at?->toIso8601String(),
            'created_at' => $comment->created_at?->toIso8601String(),
            'can_edit' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function taskCommentForMember(TaskComment $comment, OrganizationMember $viewer): array
    {
        $presented = self::taskComment($comment);
        $presented['can_edit'] = $comment->organization_member_id === $viewer->id;

        return $presented;
    }

    /**
     * @return array<string, mixed>
     */
    public static function attachment(Attachment $attachment): array
    {
        $attachment->loadMissing('uploadedByMember');

        return [
            'id' => $attachment->id,
            'original_filename' => $attachment->original_filename,
            'mime_type' => $attachment->mime_type,
            'size_bytes' => $attachment->size_bytes,
            'uploaded_by' => $attachment->uploadedByMember?->display_name,
            'created_at' => $attachment->created_at?->toIso8601String(),
            'can_delete' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function attachmentForMember(Attachment $attachment, OrganizationMember $viewer): array
    {
        $presented = self::attachment($attachment);
        $presented['can_delete'] = $attachment->uploaded_by_member_id === $viewer->id;

        return $presented;
    }

    private static function deadlineUi(Task $task): string
    {
        if ($task->deadline_date === null) {
            return 'normal';
        }

        $deadline = Carbon::parse($task->deadline_date);
        $now = now();

        if ($deadline->isPast()) {
            return 'overdue';
        }

        if ($deadline->isToday()) {
            return 'soon';
        }

        if ($deadline->lte($now->copy()->addDays(7)->endOfDay())) {
            return 'week';
        }

        return 'normal';
    }
}
