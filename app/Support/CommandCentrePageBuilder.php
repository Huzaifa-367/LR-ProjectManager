<?php

namespace App\Support;

use App\Enums\TaskKind;
use App\Models\MemberDailyFocus;
use App\Models\MemberNote;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class CommandCentrePageBuilder
{
    public function __construct(
        private readonly CommandCentreStats $stats,
        private readonly TaskVisibilityScope $taskVisibility,
        private readonly ProjectVisibilityScope $projectVisibility,
        private readonly EffectivePermissionService $permissions,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(
        Organization $organization,
        OrganizationMember $member,
        Request $request,
    ): array {
        $focusDate = $request->date('focus_date') ?? now()->startOfDay();
        $settings = $organization->settings ?? Organization::defaultSettings();
        $focusCap = (int) ($settings['focus_cap'] ?? 10);

        $member->loadMissing('role');

        $projectFilter = app(SelectedProjectManager::class)->resolveActiveProjectFilter(
            $request,
            $organization,
            $member,
        );
        $assigneeFilter = $request->integer('assignee_member_id') ?: null;

        $members = OrganizationMember::query()
            ->where('organization_id', $organization->id)
            ->where('status', 'active')
            ->orderBy('display_name')
            ->get(['id', 'display_name'])
            ->map(fn (OrganizationMember $row): array => [
                'id' => $row->id,
                'display_name' => $row->display_name,
            ])
            ->values()
            ->all();

        $focusPins = MemberDailyFocus::query()
            ->where('organization_member_id', $member->id)
            ->whereDate('focus_date', $focusDate)
            ->with(['task.project', 'task.assignees'])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (MemberDailyFocus $pin): array => CommandCentreResourcePresenter::focusPin($pin))
            ->values()
            ->all();

        $taskQuery = Task::query()
            ->forOrganization($organization->id)
            ->ofKind(TaskKind::Task)
            ->with(['project', 'assignees'])
            ->tap(fn (Builder $query) => $this->taskVisibility->apply($query, $member));

        if ($projectFilter !== null) {
            $taskQuery->where('project_id', $projectFilter);
        }

        if ($assigneeFilter !== null) {
            $taskQuery->whereHas('assignees', fn (Builder $assignees) => $assignees->where(
                'organization_members.id',
                $assigneeFilter,
            ));
        }

        $tasks = (clone $taskQuery)
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get()
            ->map(fn (Task $task): array => CommandCentreResourcePresenter::commandCentreTask($task))
            ->values()
            ->all();

        $assignedToMe = Task::query()
            ->forOrganization($organization->id)
            ->ofKind(TaskKind::Task)
            ->where('is_done', false)
            ->whereHas('assignees', fn (Builder $assignees) => $assignees->where(
                'organization_members.id',
                $member->id,
            ))
            ->tap(fn (Builder $query) => $this->taskVisibility->apply($query, $member))
            ->with(['project', 'assignees'])
            ->orderBy('deadline_date')
            ->limit(10)
            ->get()
            ->map(fn (Task $task): array => CommandCentreResourcePresenter::commandCentreTask($task))
            ->values()
            ->all();

        $reminders = Task::query()
            ->forOrganization($organization->id)
            ->ofKind(TaskKind::Reminder)
            ->where('is_done', false)
            ->tap(fn (Builder $query) => $this->taskVisibility->apply($query, $member))
            ->with(['project'])
            ->orderBy('deadline_date')
            ->limit(20)
            ->get()
            ->map(fn (Task $task): array => CommandCentreResourcePresenter::commandCentreTask($task))
            ->values()
            ->all();

        $projects = Project::query()
            ->where('organization_id', $organization->id)
            ->active()
            ->tap(fn (Builder $query) => $this->projectVisibility->apply($query, $member))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(9)
            ->get();

        $projectPermissions = $this->permissions->projectPermissionsForMemberOnProjects(
            $member,
            $projects,
        );

        $projectsPayload = $projects
            ->map(fn (Project $project): array => CommandCentreResourcePresenter::commandCentreProject($project))
            ->values()
            ->all();

        $notes = MemberNote::query()
            ->where('organization_member_id', $member->id)
            ->orderBy('sort_order')
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (MemberNote $note): array => CommandCentreResourcePresenter::memberNote($note))
            ->values()
            ->all();

        return [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'logo_url' => $organization->logo_path
                    ? asset('storage/'.$organization->logo_path)
                    : null,
            ],
            'currentMember' => [
                'id' => $member->id,
                'display_name' => $member->display_name,
                'role' => [
                    'name' => $member->role?->name,
                    'slug' => $member->role?->slug,
                ],
            ],
            'permissions' => [
                'org' => $this->permissions->orgPermissionsForMember($member),
                'projects' => $projectPermissions,
            ],
            'stats' => $this->stats->forMember($organization, $member, $focusDate),
            'focusPins' => $focusPins,
            'tasks' => $tasks,
            'reminders' => $reminders,
            'projects' => $projectsPayload,
            'notes' => $notes,
            'assignedToMe' => $assignedToMe,
            'members' => $members,
            'focusCap' => $focusCap,
            'unreadNotificationsCount' => 0,
            'filters' => [
                'focus_date' => $focusDate->toDateString(),
                'project_id' => $projectFilter,
                'assignee_member_id' => $assigneeFilter,
            ],
        ];
    }
}
