<?php

namespace App\Http\Controllers\CommandCentre;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommandCentre\StoreProjectRequest;
use App\Http\Requests\CommandCentre\UpdateProjectRequest;
use App\Models\Organization;
use App\Models\Project;
use App\Support\ActivityLogger;
use App\Support\CommandCentreResourcePresenter;
use App\Support\EffectivePermissionService;
use App\Support\OrganizationMemberResolver;
use App\Support\ProjectBootstrapService;
use App\Support\ProjectVisibilityScope;
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

        return Inertia::render('organizations/projects/show', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
            ],
            'project' => CommandCentreResourcePresenter::project($project),
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
