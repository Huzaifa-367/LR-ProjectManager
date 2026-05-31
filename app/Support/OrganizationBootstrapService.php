<?php

namespace App\Support;

use App\Enums\OrganizationMemberStatus;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\OrganizationRole;
use App\Models\OrganizationRolePermission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class OrganizationBootstrapService
{
    public function __construct(
        private readonly SelectedOrganizationManager $selectedOrganizationManager,
        private readonly MemberNotificationPreferenceSeeder $notificationPreferenceSeeder,
    ) {}

    /**
     * @param  array{name: string, slug?: string|null, settings?: array<string, mixed>}  $input
     */
    public function create(User $user, array $input, ?Request $request = null): Organization
    {
        return DB::transaction(function () use ($user, $input, $request): Organization {
            $slug = $this->resolveUniqueSlug($input['slug'] ?? Str::slug($input['name']));
            $timezone = $input['settings']['timezone'] ?? null;
            $settings = Organization::defaultSettings(is_string($timezone) ? $timezone : null);

            if (isset($input['settings']['focus_cap'])) {
                $settings['focus_cap'] = (int) $input['settings']['focus_cap'];
            }

            if (isset($input['settings']['ai_enabled'])) {
                $settings['ai_enabled'] = (bool) $input['settings']['ai_enabled'];
            }

            $organization = Organization::query()->create([
                'name' => $input['name'],
                'slug' => $slug,
                'owner_user_id' => $user->id,
                'settings' => $settings,
            ]);

            $ownerRoleId = null;

            foreach (CommandCentreRoleTemplateRegistry::orgRoles() as $template) {
                $role = OrganizationRole::query()->create([
                    'organization_id' => $organization->id,
                    'name' => $template['name'],
                    'slug' => $template['slug'],
                    'description' => null,
                    'is_system' => $template['is_system'],
                    'sort_order' => $template['sort_order'],
                ]);

                foreach ($template['permissions'] as $permission) {
                    OrganizationRolePermission::query()->create([
                        'organization_role_id' => $role->id,
                        'permission' => $permission,
                    ]);
                }

                if ($template['slug'] === 'owner') {
                    $ownerRoleId = $role->id;
                }
            }

            $isFirstOrg = ! OrganizationMember::query()
                ->where('user_id', $user->id)
                ->where('status', OrganizationMemberStatus::Active->value)
                ->exists();

            OrganizationMember::query()->create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'organization_role_id' => $ownerRoleId,
                'display_name' => $user->name,
                'email' => $user->email,
                'status' => OrganizationMemberStatus::Active->value,
                'is_primary_org' => $isFirstOrg,
                'joined_at' => now(),
            ]);

            $ownerMember = OrganizationMember::query()
                ->where('organization_id', $organization->id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            $this->notificationPreferenceSeeder->seedForMember($ownerMember);

            if ($request !== null) {
                $this->selectedOrganizationManager->setSelectedOrganizationId($request, $organization->id);
            }

            return $organization->fresh(['roles.permissions']);
        });
    }

    private function resolveUniqueSlug(string $baseSlug): string
    {
        $slug = Str::slug($baseSlug) ?: 'organization';
        $original = $slug;
        $counter = 1;

        while (Organization::query()->where('slug', $slug)->exists()) {
            $slug = $original.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
