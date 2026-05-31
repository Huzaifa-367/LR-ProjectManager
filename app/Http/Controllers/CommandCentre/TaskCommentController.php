<?php

namespace App\Http\Controllers\CommandCentre;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommandCentre\StoreTaskCommentRequest;
use App\Http\Requests\CommandCentre\UpdateTaskCommentRequest;
use App\Models\Organization;
use App\Models\Task;
use App\Models\TaskComment;
use App\Support\ActivityLogger;
use App\Support\EffectivePermissionService;
use App\Support\OrganizationMemberResolver;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class TaskCommentController extends Controller
{
    public function store(
        StoreTaskCommentRequest $request,
        Organization $organization,
        Task $task,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization($request->user(), $organization);

        abort_unless($task->organization_id === $organization->id, 404);
        abort_unless($permissions->memberCan($member, 'org.task-comments.store'), 403);
        abort_unless($permissions->memberCanViewTask($member, $task), 403);

        $validated = $request->validated();

        if (isset($validated['parent_id'])) {
            $parent = TaskComment::query()->find($validated['parent_id']);
            abort_unless($parent !== null && $parent->task_id === $task->id, 422);
        }

        $comment = TaskComment::query()->create([
            'task_id' => $task->id,
            'organization_member_id' => $member->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'body' => $validated['body'],
        ]);

        app(ActivityLogger::class)->log(
            $organization->id,
            $member->id,
            $comment,
            'task_comment.created',
            ['task_id' => $task->id],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Comment added.')]);

        return back();
    }

    public function update(
        UpdateTaskCommentRequest $request,
        Organization $organization,
        Task $task,
        TaskComment $taskComment,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization($request->user(), $organization);

        abort_unless($task->organization_id === $organization->id, 404);
        abort_unless($taskComment->task_id === $task->id, 404);
        abort_unless($permissions->memberCan($member, 'org.task-comments.update'), 403);
        abort_unless($taskComment->organization_member_id === $member->id, 403);

        $taskComment->update([
            'body' => $request->validated('body'),
            'edited_at' => now(),
        ]);

        app(ActivityLogger::class)->logForAuthenticatedUser(
            $taskComment,
            'task_comment.updated',
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Comment updated.')]);

        return back();
    }

    public function destroy(
        Organization $organization,
        Task $task,
        TaskComment $taskComment,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless($task->organization_id === $organization->id, 404);
        abort_unless($taskComment->task_id === $task->id, 404);
        abort_unless($permissions->memberCan($member, 'org.task-comments.destroy'), 403);
        abort_unless($taskComment->organization_member_id === $member->id, 403);

        $taskComment->delete();

        app(ActivityLogger::class)->logForAuthenticatedUser(
            $taskComment,
            'task_comment.deleted',
            ['task_id' => $task->id],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Comment deleted.')]);

        return back();
    }
}
