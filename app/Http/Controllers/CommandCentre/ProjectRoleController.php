<?php

namespace App\Http\Controllers\CommandCentre;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommandCentre\SyncProjectRolePermissionsRequest;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectRole;
use App\Models\ProjectRolePermission;
use App\Support\CommandCentrePermissionRegistry;
use App\Support\CommandCentreResourcePresenter;
use App\Support\EffectivePermissionService;
use App\Support\OrganizationMemberResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProjectRoleController extends Controller
{
    public function index(
        Organization $organization,
        Project $project,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): Response {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless(
            $permissions->memberCanOnProject($member, $project, 'project.roles.index'),
            403,
        );

        $roles = $project->roles()
            ->with('permissions')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (ProjectRole $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'is_default' => $role->is_default,
                'permissions' => $role->permissionSlugs(),
            ])
            ->values()
            ->all();

        return Inertia::render('projects/settings/roles', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'project' => CommandCentreResourcePresenter::project($project),
            'roles' => $roles,
            'permissionGroups' => CommandCentrePermissionRegistry::projectGroups(),
            'permissions' => CommandCentreResourcePresenter::permissions($permissions, $member, $project),
        ]);
    }

    public function syncPermissions(
        SyncProjectRolePermissionsRequest $request,
        Organization $organization,
        Project $project,
        ProjectRole $projectRole,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization($request->user(), $organization);

        abort_unless(
            $permissions->memberCanOnProject($member, $project, 'project.roles.permissions.sync'),
            403,
        );

        abort_unless($projectRole->project_id === $project->id, 404);

        $permissionSlugs = $request->validated('permissions');

        DB::transaction(function () use ($projectRole, $permissionSlugs): void {
            $projectRole->permissions()->delete();

            foreach ($permissionSlugs as $permission) {
                ProjectRolePermission::query()->create([
                    'project_role_id' => $projectRole->id,
                    'permission' => $permission,
                ]);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project role permissions updated.')]);

        return back();
    }
}
