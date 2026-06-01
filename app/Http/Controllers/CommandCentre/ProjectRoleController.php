<?php

namespace App\Http\Controllers\CommandCentre;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommandCentre\StoreProjectRoleRequest;
use App\Http\Requests\CommandCentre\SyncProjectRolePermissionsRequest;
use App\Http\Requests\CommandCentre\UpdateProjectRoleRequest;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectRole;
use App\Models\ProjectRolePermission;
use App\Support\CommandCentrePermissionRegistry;
use App\Support\CommandCentreResourcePresenter;
use App\Support\EffectivePermissionService;
use App\Support\OrganizationMemberResolver;
use App\Support\ProjectRoleSlugGenerator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
            ->withCount('permissions')
            ->withCount(['members as members_count'])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (ProjectRole $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'is_default' => $role->is_default,
                'is_system' => ProjectRoleSlugGenerator::isSystemSlug($role->slug),
                'permissions_count' => (int) $role->permissions_count,
                'members_count' => (int) $role->members_count,
            ])
            ->values()
            ->all();

        return Inertia::render('projects/settings/roles/index', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'project' => CommandCentreResourcePresenter::project($project),
            'roles' => $roles,
            'permissionGroups' => $this->permissionGroups(),
            'permissions' => CommandCentreResourcePresenter::permissions($permissions, $member, $project),
        ]);
    }

    public function store(
        StoreProjectRoleRequest $request,
        Organization $organization,
        Project $project,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization($request->user(), $organization);

        abort_unless(
            $permissions->memberCanOnProject($member, $project, 'project.roles.store'),
            403,
        );

        $validated = $request->validated();
        $permissionSlugs = $validated['permissions'] ?? [];
        $sortOrder = (int) $project->roles()->max('sort_order') + 1;

        $role = DB::transaction(function () use (
            $project,
            $validated,
            $permissionSlugs,
            $sortOrder,
        ): ProjectRole {
            $role = ProjectRole::query()->create([
                'project_id' => $project->id,
                'name' => $validated['name'],
                'slug' => ProjectRoleSlugGenerator::uniqueForProject(
                    $project,
                    $validated['name'],
                ),
                'is_default' => false,
                'sort_order' => $sortOrder,
            ]);

            $this->replaceRolePermissions($role, $permissionSlugs);

            return $role;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project role created.')]);

        return to_route('projects.roles.show', [$organization, $project, $role]);
    }

    public function show(
        Organization $organization,
        Project $project,
        ProjectRole $projectRole,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): Response {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless(
            $permissions->memberCanOnProject($member, $project, 'project.roles.show'),
            403,
        );

        abort_unless($projectRole->project_id === $project->id, 404);

        $projectRole->load('permissions');
        $membersCount = ProjectMember::query()
            ->where('project_id', $project->id)
            ->where('project_role_id', $projectRole->id)
            ->count();

        return Inertia::render('projects/settings/roles/show', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'project' => CommandCentreResourcePresenter::project($project),
            'role' => [
                'id' => $projectRole->id,
                'name' => $projectRole->name,
                'slug' => $projectRole->slug,
                'is_default' => $projectRole->is_default,
                'is_system' => ProjectRoleSlugGenerator::isSystemSlug($projectRole->slug),
                'members_count' => $membersCount,
                'permissions' => $projectRole->permissionSlugs(),
            ],
            'permissionGroups' => $this->permissionGroups(),
            'permissions' => CommandCentreResourcePresenter::permissions($permissions, $member, $project),
        ]);
    }

    public function update(
        UpdateProjectRoleRequest $request,
        Organization $organization,
        Project $project,
        ProjectRole $projectRole,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization($request->user(), $organization);

        abort_unless(
            $permissions->memberCanOnProject($member, $project, 'project.roles.update'),
            403,
        );

        abort_unless($projectRole->project_id === $project->id, 404);

        if (ProjectRoleSlugGenerator::isSystemSlug($projectRole->slug)) {
            Inertia::flash('toast', ['type' => 'success', 'message' => __('System project roles cannot be renamed.')]);

            return back();
        }

        $validated = $request->validated();

        $projectRole->update([
            'name' => $validated['name'],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project role updated.')]);

        return back();
    }

    public function destroy(
        Organization $organization,
        Project $project,
        ProjectRole $projectRole,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless(
            $permissions->memberCanOnProject($member, $project, 'project.roles.destroy'),
            403,
        );

        abort_unless($projectRole->project_id === $project->id, 404);

        if (ProjectRoleSlugGenerator::isSystemSlug($projectRole->slug)) {
            throw new AuthorizationException(__('System project roles cannot be deleted.'));
        }

        if (
            ProjectMember::query()
                ->where('project_id', $project->id)
                ->where('project_role_id', $projectRole->id)
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'role' => __('Reassign project team members before deleting this role.'),
            ]);
        }

        $projectRole->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project role deleted.')]);

        return to_route('projects.roles.index', [$organization, $project]);
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
            $this->replaceRolePermissions($projectRole, $permissionSlugs);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project role permissions updated.')]);

        return back();
    }

    /**
     * @param  list<string>  $permissionSlugs
     */
    private function replaceRolePermissions(
        ProjectRole $projectRole,
        array $permissionSlugs,
    ): void {
        $projectRole->permissions()->delete();

        foreach ($permissionSlugs as $permission) {
            ProjectRolePermission::query()->create([
                'project_role_id' => $projectRole->id,
                'permission' => $permission,
            ]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function permissionGroups(): array
    {
        return collect(CommandCentrePermissionRegistry::projectGroups())
            ->map(fn (array $group, string $key): array => [
                'key' => $key,
                'label' => $group['label'],
                'description' => $group['description'] ?? null,
                'permissions' => collect($group['permissions'])
                    ->map(fn (string $label, string $name): array => [
                        'name' => $name,
                        'label' => $label,
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }
}
