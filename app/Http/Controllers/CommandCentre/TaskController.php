<?php

namespace App\Http\Controllers\CommandCentre;

use App\Enums\TaskKind;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CommandCentre\StoreTaskRequest;
use App\Http\Requests\CommandCentre\SyncTaskAssigneesRequest;
use App\Http\Requests\CommandCentre\UpdateTaskRequest;
use App\Http\Requests\CommandCentre\UpdateTaskStatusRequest;
use App\Models\Attachment;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Support\ActivityLogger;
use App\Support\CommandCentreResourcePresenter;
use App\Support\CreateFollowUpReminder;
use App\Support\EffectivePermissionService;
use App\Support\OrganizationMemberResolver;
use App\Support\ProjectVisibilityScope;
use App\Support\NotificationDispatcher;
use App\Support\ScheduleTaskDeadlineReminders;
use App\Support\SelectedProjectManager;
use App\Support\SyncMemberDailyFocus;
use App\Support\TaskVisibilityScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(
        Organization $organization,
        OrganizationMemberResolver $memberResolver,
        TaskVisibilityScope $visibilityScope,
        EffectivePermissionService $permissions,
        Request $request,
    ): Response {
        $member = $memberResolver->requireForOrganization($request->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.tasks.index'), 403);

        $projectFilter = app(SelectedProjectManager::class)->resolveActiveProjectFilter(
            $request,
            $organization,
            $member,
        );

        $query = Task::query()
            ->forOrganization($organization->id)
            ->with(['project', 'assignees'])
            ->tap(fn ($builder) => $visibilityScope->apply($builder, $member));

        if ($projectFilter !== null) {
            $query->where('project_id', $projectFilter);
        }

        if ($request->filled('kind')) {
            $kind = TaskKind::tryFrom((string) $request->string('kind'));

            if ($kind !== null) {
                $query->ofKind($kind);
            }
        }

        $tasks = $query
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (Task $task): array => CommandCentreResourcePresenter::task($task))
            ->values()
            ->all();

        $projects = Project::query()
            ->active()
            ->tap(fn ($builder) => app(ProjectVisibilityScope::class)->apply($builder, $member))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->all();

        return Inertia::render('tasks/index', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
            ],
            'tasks' => $tasks,
            'projects' => $projects,
            'filters' => [
                'project_id' => $projectFilter,
                'kind' => $request->string('kind')->toString() ?: null,
            ],
            'permissions' => CommandCentreResourcePresenter::permissions($permissions, $member),
        ]);
    }

    public function store(
        StoreTaskRequest $request,
        Organization $organization,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization($request->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.tasks.store'), 403);

        $validated = $request->validated();

        $task = Task::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $validated['project_id'],
            'kind' => $validated['kind'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'created_by_member_id' => $member->id,
            'priority' => $validated['priority'] ?? null,
            'status' => $validated['status'] ?? TaskStatus::Pending,
            'deadline_type' => $validated['deadline_type'] ?? null,
            'deadline_date' => $validated['deadline_date'] ?? null,
            'external_link' => $validated['external_link'] ?? null,
            'meta' => $validated['meta'] ?? null,
        ]);

        $newAssigneeIds = $this->syncAssigneeIds($task, $member, $validated['assignee_member_ids'] ?? []);
        $task->load(['assignees', 'organization']);
        $this->notifyNewAssignees($task, $member, $newAssigneeIds);
        app(SyncMemberDailyFocus::class)->syncForTask($task->fresh(['assignees']));
        app(ScheduleTaskDeadlineReminders::class)->syncForTask($task->fresh(['assignees', 'organization']));
        app(ActivityLogger::class)->log($organization->id, $member->id, $task, 'task.created', [
            'title' => $task->title,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task created.')]);

        return to_route('organizations.tasks.show', [$organization, $task]);
    }

    public function show(
        Organization $organization,
        Task $task,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): Response {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless($task->organization_id === $organization->id, 404);
        abort_unless($permissions->memberCanViewTask($member, $task), 403);

        $comments = $task->comments()
            ->with('author')
            ->whereNull('parent_id')
            ->orderBy('created_at')
            ->get()
            ->map(fn (TaskComment $comment): array => CommandCentreResourcePresenter::taskCommentForMember($comment, $member))
            ->values()
            ->all();

        $attachments = $task->attachments()
            ->with('uploadedByMember')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Attachment $attachment): array => CommandCentreResourcePresenter::attachmentForMember($attachment, $member))
            ->values()
            ->all();

        return Inertia::render('tasks/show', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
            ],
            'task' => CommandCentreResourcePresenter::task($task),
            'comments' => $comments,
            'attachments' => $attachments,
            'permissions' => CommandCentreResourcePresenter::permissions(
                $permissions,
                $member,
                $task->project,
            ),
        ]);
    }

    public function update(
        UpdateTaskRequest $request,
        Organization $organization,
        Task $task,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization($request->user(), $organization);

        abort_unless($task->organization_id === $organization->id, 404);
        abort_unless($permissions->memberCanUpdateTask($member, $task), 403);

        $task->update($request->validated());
        app(SyncMemberDailyFocus::class)->syncForTask($task->fresh(['assignees']));
        app(ScheduleTaskDeadlineReminders::class)->syncForTask($task->fresh(['assignees', 'organization']));
        app(ActivityLogger::class)->logForAuthenticatedUser($task, 'task.updated');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task updated.')]);

        return back();
    }

    public function destroy(
        Organization $organization,
        Task $task,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless($task->organization_id === $organization->id, 404);
        abort_unless($permissions->memberCanDeleteTask($member, $task), 403);

        $task->delete();
        app(ActivityLogger::class)->logForAuthenticatedUser($task, 'task.deleted', [
            'title' => $task->title,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task deleted.')]);

        return to_route('organizations.tasks.index', $organization);
    }

    public function updateStatus(
        UpdateTaskStatusRequest $request,
        Organization $organization,
        Task $task,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization($request->user(), $organization);

        abort_unless($task->organization_id === $organization->id, 404);
        abort_unless($permissions->memberCan($member, 'org.tasks.status.update'), 403);
        abort_unless($permissions->memberCanUpdateTask($member, $task), 403);

        $status = TaskStatus::from($request->validated('status'));

        $task->update(['status' => $status]);

        if ($status === TaskStatus::FollowUp) {
            app(CreateFollowUpReminder::class)->createForTask($task->fresh(['assignees']), $member);
        }

        app(ActivityLogger::class)->logForAuthenticatedUser($task, 'task.status_changed', [
            'status' => $status->value,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task status updated.')]);

        return back();
    }

    public function syncAssignees(
        SyncTaskAssigneesRequest $request,
        Organization $organization,
        Task $task,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization($request->user(), $organization);

        abort_unless($task->organization_id === $organization->id, 404);
        abort_unless($permissions->memberCan($member, 'org.tasks.assignees.sync'), 403);
        abort_unless($permissions->memberCanUpdateTask($member, $task), 403);

        $newAssigneeIds = $this->syncAssigneeIds($task, $member, $request->validated('assignee_member_ids'));
        $task->load(['assignees', 'organization']);
        $this->notifyNewAssignees($task, $member, $newAssigneeIds);
        app(SyncMemberDailyFocus::class)->syncForTask($task->fresh(['assignees']));
        app(ScheduleTaskDeadlineReminders::class)->syncForTask($task->fresh(['assignees', 'organization']));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task assignees updated.')]);

        return back();
    }

    public function toggleDone(
        Organization $organization,
        Task $task,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless($task->organization_id === $organization->id, 404);
        abort_unless($permissions->memberCan($member, 'org.tasks.toggle-done'), 403);
        abort_unless($permissions->memberCanUpdateTask($member, $task), 403);

        $isDone = ! $task->is_done;

        $task->update([
            'is_done' => $isDone,
            'completed_at' => $isDone ? now() : null,
            'completed_by_member_id' => $isDone ? $member->id : null,
            'status' => $isDone ? TaskStatus::Done : TaskStatus::Pending,
        ]);
        app(SyncMemberDailyFocus::class)->syncForTask($task->fresh(['assignees']));
        if ($isDone) {
            app(ScheduleTaskDeadlineReminders::class)->cancelForTask($task);
        } else {
            app(ScheduleTaskDeadlineReminders::class)->syncForTask($task->fresh(['assignees', 'organization']));
        }
        app(ActivityLogger::class)->logForAuthenticatedUser(
            $task,
            $isDone ? 'task.completed' : 'task.reopened',
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $isDone ? __('Task marked done.') : __('Task marked incomplete.'),
        ]);

        return back();
    }

    /**
     * @param  list<int>  $assigneeMemberIds
     * @return list<int>
     */
    private function syncAssigneeIds(Task $task, OrganizationMember $member, array $assigneeMemberIds): array
    {
        $previousIds = $task->assignees()->pluck('organization_members.id')->all();

        $syncPayload = [];

        foreach (array_values($assigneeMemberIds) as $index => $assigneeMemberId) {
            $syncPayload[(int) $assigneeMemberId] = [
                'is_primary' => $index === 0,
                'assigned_at' => now(),
                'assigned_by_member_id' => $member->id,
            ];
        }

        $task->assignees()->sync($syncPayload);

        return array_values(array_diff(
            array_map(intval(...), array_values($assigneeMemberIds)),
            array_map(intval(...), $previousIds),
        ));
    }

    /**
     * @param  list<int>  $newAssigneeMemberIds
     */
    private function notifyNewAssignees(Task $task, OrganizationMember $assignedBy, array $newAssigneeMemberIds): void
    {
        if ($newAssigneeMemberIds === []) {
            return;
        }

        $dispatcher = app(NotificationDispatcher::class);
        $newAssignees = OrganizationMember::query()
            ->where('organization_id', $task->organization_id)
            ->whereIn('id', $newAssigneeMemberIds)
            ->get();

        foreach ($newAssignees as $assignee) {
            if ($assignee->id === $assignedBy->id) {
                continue;
            }

            $dispatcher->dispatchTaskAssigned($task, $assignee, $assignedBy);
        }
    }
}
