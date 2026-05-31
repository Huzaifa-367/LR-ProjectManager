<?php

namespace App\Http\Controllers;

use App\Http\Requests\Organizations\SyncOrganizationRolePermissionsRequest;
use App\Models\Organization;
use App\Models\OrganizationRole;
use App\Models\OrganizationRolePermission;
use App\Support\ActivityLogger;
use App\Support\CommandCentrePermissionRegistry;
use App\Support\CommandCentreResourcePresenter;
use App\Support\EffectivePermissionService;
use App\Support\OrganizationMemberResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
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
            'permissions' => CommandCentreResourcePresenter::permissions($permissions, $member),
        ]);
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
            $organizationRole->permissions()->delete();

            foreach ($permissionSlugs as $permission) {
                OrganizationRolePermission::query()->create([
                    'organization_role_id' => $organizationRole->id,
                    'permission' => $permission,
                ]);
            }
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
