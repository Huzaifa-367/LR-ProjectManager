<?php

namespace App\Http\Controllers\CommandCentre;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommandCentre\StoreProjectRequest;
use App\Http\Requests\CommandCentre\UpdateProjectRequest;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Support\ActivityLogger;
use App\Support\CommandCentreResourcePresenter;
use App\Support\EffectivePermissionService;
use App\Support\OrganizationMemberResolver;
use App\Support\ProjectBootstrapService;
use App\Support\ProjectVisibilityScope;
use App\Support\TaskVisibilityScope;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(
        Organization $organization,
        OrganizationMemberResolver $memberResolver,
        ProjectVisibilityScope $visibilityScope,
        EffectivePermissionService $permissions,
    ): Response {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.projects.index'), 403);

        $projects = Project::query()
            ->active()
            ->tap(fn ($query) => $visibilityScope->apply($query, $member))
            ->orderBy('name')
            ->get()
            ->map(fn (Project $project): array => CommandCentreResourcePresenter::project($project))
            ->values()
            ->all();

        return Inertia::render('organizations/projects/index', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
            ],
            'projects' => $projects,
            'permissions' => CommandCentreResourcePresenter::permissions($permissions, $member),
        ]);
    }

    public function store(
        StoreProjectRequest $request,
        Organization $organization,
        OrganizationMemberResolver $memberResolver,
        ProjectBootstrapService $bootstrapService,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization($request->user(), $organization);

        $validated = $request->validated();
        $team = $validated['team'] ?? [];

        $project = $bootstrapService->create(
            $organization,
            $member,
            $validated,
            $team,
        );

        app(ActivityLogger::class)->log($organization->id, $member->id, $project, 'project.created', [
            'name' => $project->name,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project created.')]);

        return to_route('organizations.projects.show', [$organization, $project]);
    }

    public function show(
        Organization $organization,
        Project $project,
        OrganizationMemberResolver $memberResolver,
        ProjectVisibilityScope $visibilityScope,
        EffectivePermissionService $permissions,
    ): Response {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.projects.show'), 403);

        $visible = Project::query()
            ->whereKey($project->id)
            ->tap(fn ($query) => $visibilityScope->apply($query, $member))
            ->exists();

        abort_unless($visible, 404);

        $tasks = Task::query()
            ->forOrganization($organization->id)
            ->where('project_id', $project->id)
            ->with(['assignees'])
            ->tap(fn ($builder) => app(TaskVisibilityScope::class)->apply($builder, $member))
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (Task $task): array => CommandCentreResourcePresenter::task($task))
            ->values()
            ->all();

        $team = ProjectMember::query()
            ->where('project_id', $project->id)
            ->with(['organizationMember', 'role'])
            ->orderBy('joined_at')
            ->get()
            ->map(fn (ProjectMember $projectMember): array => [
                'id' => $projectMember->id,
                'organization_member_id' => $projectMember->organization_member_id,
                'display_name' => $projectMember->organizationMember?->display_name,
                'role_name' => $projectMember->role?->name,
            ])
            ->values()
            ->all();

        $openTaskCount = collect($tasks)->filter(
            fn (array $task): bool => ($task['kind'] ?? 'task') === 'task'
                && ($task['is_done'] ?? false) === false,
        )->count();

        $doneTaskCount = collect($tasks)->filter(
            fn (array $task): bool => ($task['kind'] ?? 'task') === 'task'
                && ($task['is_done'] ?? false) === true,
        )->count();

        return Inertia::render('organizations/projects/show', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
            ],
            'project' => CommandCentreResourcePresenter::project($project),
            'tasks' => $tasks,
            'team' => $team,
            'taskSummary' => [
                'open' => $openTaskCount,
                'done' => $doneTaskCount,
            ],
            'permissions' => CommandCentreResourcePresenter::permissions($permissions, $member, $project),
        ]);
    }

    public function update(
        UpdateProjectRequest $request,
        Organization $organization,
        Project $project,
        OrganizationMemberResolver $memberResolver,
        ProjectVisibilityScope $visibilityScope,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization($request->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.projects.update'), 403);

        $visible = Project::query()
            ->whereKey($project->id)
            ->tap(fn ($query) => $visibilityScope->apply($query, $member))
            ->exists();

        abort_unless($visible, 404);

        $project->update($request->validated());
        app(ActivityLogger::class)->logForAuthenticatedUser($project, 'project.updated');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project updated.')]);

        return back();
    }

    public function archive(
        Organization $organization,
        Project $project,
        OrganizationMemberResolver $memberResolver,
        ProjectVisibilityScope $visibilityScope,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.projects.archive'), 403);

        $visible = Project::query()
            ->whereKey($project->id)
            ->tap(fn ($query) => $visibilityScope->apply($query, $member))
            ->exists();

        abort_unless($visible, 404);

        $project->update(['archived_at' => now()]);
        app(ActivityLogger::class)->logForAuthenticatedUser($project, 'project.archived');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project archived.')]);

        return to_route('organizations.projects.index', $organization);
    }
}
