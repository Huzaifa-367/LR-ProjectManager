<?php

namespace App\Http\Controllers;

use App\Http\Requests\Organizations\StoreOrganizationRoleRequest;
use App\Http\Requests\Organizations\SyncOrganizationRolePermissionsRequest;
use App\Http\Requests\Organizations\UpdateOrganizationRoleRequest;
use App\Models\Organization;
use App\Models\OrganizationRole;
use App\Models\OrganizationRolePermission;
use App\Support\ActivityLogger;
use App\Support\CommandCentrePermissionRegistry;
use App\Support\CommandCentreResourcePresenter;
use App\Support\EffectivePermissionService;
use App\Support\OrganizationMemberResolver;
use App\Support\OrganizationRoleSlugGenerator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationRoleController extends Controller
{
    public function index(
        Organization $organization,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): Response {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.roles.index'), 403);

        $roles = $organization->roles()
            ->withCount('permissions')
            ->withCount('members')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (OrganizationRole $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'description' => $role->description,
                'is_system' => $role->is_system,
                'permissions_count' => (int) $role->permissions_count,
                'members_count' => (int) $role->members_count,
            ])
            ->values()
            ->all();

        return Inertia::render('organizations/settings/roles/index', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'roles' => $roles,
            'permissionGroups' => $this->permissionGroups(),
            'permissions' => CommandCentreResourcePresenter::permissions($permissions, $member),
        ]);
    }

    public function store(
        StoreOrganizationRoleRequest $request,
        Organization $organization,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization($request->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.roles.store'), 403);

        $validated = $request->validated();
        $permissionSlugs = $validated['permissions'] ?? [];
        $sortOrder = (int) $organization->roles()->max('sort_order') + 1;

        $role = DB::transaction(function () use (
            $organization,
            $validated,
            $permissionSlugs,
            $sortOrder,
        ): OrganizationRole {
            $role = OrganizationRole::query()->create([
                'organization_id' => $organization->id,
                'name' => $validated['name'],
                'slug' => OrganizationRoleSlugGenerator::uniqueForOrganization(
                    $organization,
                    $validated['name'],
                ),
                'description' => $validated['description'] ?? null,
                'is_system' => false,
                'sort_order' => $sortOrder,
            ]);

            $this->replaceRolePermissions($role, $permissionSlugs);

            return $role;
        });

        app(ActivityLogger::class)->log(
            $organization->id,
            $member->id,
            $role,
            'role.created',
            ['name' => $role->name, 'slug' => $role->slug],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role created.')]);

        return to_route('organizations.roles.show', [$organization, $role]);
    }

    public function update(
        UpdateOrganizationRoleRequest $request,
        Organization $organization,
        OrganizationRole $organizationRole,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization($request->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.roles.update'), 403);
        abort_unless($organizationRole->organization_id === $organization->id, 404);

        $validated = $request->validated();

        if ($organizationRole->is_system) {
            $organizationRole->update([
                'description' => $validated['description'] ?? null,
            ]);
        } else {
            $organizationRole->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);
        }

        app(ActivityLogger::class)->log(
            $organization->id,
            $member->id,
            $organizationRole,
            'role.updated',
            ['name' => $organizationRole->name],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role updated.')]);

        return back();
    }

    public function destroy(
        Organization $organization,
        OrganizationRole $organizationRole,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.roles.destroy'), 403);
        abort_unless($organizationRole->organization_id === $organization->id, 404);

        if ($organizationRole->is_system) {
            throw new AuthorizationException(__('System roles cannot be deleted.'));
        }

        if ($organizationRole->members()->exists()) {
            throw ValidationException::withMessages([
                'role' => __('Remove or reassign members before deleting this role.'),
            ]);
        }

        $roleName = $organizationRole->name;

        $organizationRole->delete();

        app(ActivityLogger::class)->log(
            $organization->id,
            $member->id,
            $organization,
            'role.deleted',
            ['name' => $roleName, 'slug' => $organizationRole->slug],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role deleted.')]);

        return to_route('organizations.roles.index', $organization);
    }

    public function show(
        Organization $organization,
        OrganizationRole $organizationRole,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): Response {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.roles.show'), 403);
        abort_unless($organizationRole->organization_id === $organization->id, 404);

        $organizationRole->load('permissions');

        $organizationRole->loadCount('members');

        return Inertia::render('organizations/settings/roles/show', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'role' => [
                'id' => $organizationRole->id,
                'name' => $organizationRole->name,
                'slug' => $organizationRole->slug,
                'description' => $organizationRole->description,
                'is_system' => $organizationRole->is_system,
                'members_count' => (int) $organizationRole->members_count,
                'permissions' => $organizationRole->permissionSlugs(),
            ],
            'permissionGroups' => $this->permissionGroups(),
            'lockedPermissions' => $this->lockedPermissionsForRole($organizationRole),
            'permissions' => CommandCentreResourcePresenter::permissions($permissions, $member),
        ]);
    }

    public function syncPermissions(
        SyncOrganizationRolePermissionsRequest $request,
        Organization $organization,
        OrganizationRole $organizationRole,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization($request->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.roles.permissions.sync'), 403);
        abort_unless($organizationRole->organization_id === $organization->id, 404);

        $permissionSlugs = $request->validated('permissions');
        $locked = $this->lockedPermissionsForRole($organizationRole);
        $previousPermissions = $organizationRole->permissionSlugs();

        foreach ($locked as $lockedSlug) {
            if (! in_array($lockedSlug, $permissionSlugs, true)) {
                $permissionSlugs[] = $lockedSlug;
            }
        }

        DB::transaction(function () use ($organizationRole, $permissionSlugs): void {
            $this->replaceRolePermissions($organizationRole, $permissionSlugs);
        });

        app(ActivityLogger::class)->log(
            $organization->id,
            $member->id,
            $organizationRole,
            'role.permissions_synced',
            [
                'old' => $previousPermissions,
                'new' => $permissionSlugs,
            ],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role permissions updated.')]);

        return back();
    }

    /**
     * @param  list<string>  $permissionSlugs
     */
    private function replaceRolePermissions(
        OrganizationRole $organizationRole,
        array $permissionSlugs,
    ): void {
        $locked = $this->lockedPermissionsForRole($organizationRole);

        foreach ($locked as $lockedSlug) {
            if (! in_array($lockedSlug, $permissionSlugs, true)) {
                $permissionSlugs[] = $lockedSlug;
            }
        }

        $organizationRole->permissions()->delete();

        foreach ($permissionSlugs as $permission) {
            OrganizationRolePermission::query()->create([
                'organization_role_id' => $organizationRole->id,
                'permission' => $permission,
            ]);
        }
    }

    /**
     * @return list<string>
     */
    private function lockedPermissionsForRole(OrganizationRole $role): array
    {
        if ($role->slug === 'owner') {
            return ['org.organizations.destroy'];
        }

        return [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function permissionGroups(): array
    {
        return collect(CommandCentrePermissionRegistry::orgGroups())
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
