<?php

namespace App\Http\Controllers\CommandCentre;

use App\Enums\OrganizationMemberStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CommandCentre\StoreProjectMemberRequest;
use App\Http\Requests\CommandCentre\UpdateProjectMemberRequest;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Support\CommandCentreResourcePresenter;
use App\Support\EffectivePermissionService;
use App\Support\OrganizationMemberResolver;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProjectMemberController extends Controller
{
    public function index(
        Organization $organization,
        Project $project,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): Response {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless(
            $permissions->memberCanOnProject($member, $project, 'project.members.index'),
            403,
        );

        $team = ProjectMember::query()
            ->where('project_id', $project->id)
            ->with(['organizationMember', 'role'])
            ->orderBy('joined_at')
            ->get()
            ->map(fn (ProjectMember $projectMember): array => [
                'id' => $projectMember->id,
                'organization_member_id' => $projectMember->organization_member_id,
                'display_name' => $projectMember->organizationMember?->display_name,
                'role' => [
                    'id' => $projectMember->role?->id,
                    'name' => $projectMember->role?->name,
                    'slug' => $projectMember->role?->slug,
                ],
                'joined_at' => $projectMember->joined_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        $roles = $project->roles()->orderBy('sort_order')->get(['id', 'name', 'slug'])->all();

        $teamMemberIds = collect($team)->pluck('organization_member_id')->all();

        $availableMembers = OrganizationMember::query()
            ->where('organization_id', $organization->id)
            ->where('status', OrganizationMemberStatus::Active)
            ->when(
                $teamMemberIds !== [],
                fn ($query) => $query->whereNotIn('id', $teamMemberIds),
            )
            ->orderBy('display_name')
            ->get(['id', 'display_name', 'email', 'title'])
            ->map(fn (OrganizationMember $organizationMember): array => [
                'id' => $organizationMember->id,
                'display_name' => $organizationMember->display_name,
                'email' => $organizationMember->email,
                'title' => $organizationMember->title,
            ])
            ->values()
            ->all();

        return Inertia::render('projects/settings/team', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'project' => CommandCentreResourcePresenter::project($project),
            'team' => $team,
            'roles' => $roles,
            'availableMembers' => $availableMembers,
            'permissions' => CommandCentreResourcePresenter::permissions($permissions, $member, $project),
        ]);
    }

    public function store(
        StoreProjectMemberRequest $request,
        Organization $organization,
        Project $project,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization($request->user(), $organization);

        abort_unless(
            $permissions->memberCanOnProject($member, $project, 'project.members.store'),
            403,
        );

        $validated = $request->validated();

        ProjectMember::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'organization_member_id' => $validated['organization_member_id'],
            ],
            [
                'project_role_id' => $validated['project_role_id'],
                'joined_at' => now(),
            ],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Team member added.')]);

        return back();
    }

    public function update(
        UpdateProjectMemberRequest $request,
        Organization $organization,
        Project $project,
        ProjectMember $projectMember,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization($request->user(), $organization);

        abort_unless(
            $permissions->memberCanOnProject($member, $project, 'project.members.update'),
            403,
        );

        abort_unless($projectMember->project_id === $project->id, 404);

        $projectMember->update([
            'project_role_id' => $request->validated('project_role_id'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Team member updated.')]);

        return back();
    }

    public function destroy(
        Organization $organization,
        Project $project,
        ProjectMember $projectMember,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless(
            $permissions->memberCanOnProject($member, $project, 'project.members.destroy'),
            403,
        );

        abort_unless($projectMember->project_id === $project->id, 404);

        $projectMember->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Team member removed.')]);

        return back();
    }
}
