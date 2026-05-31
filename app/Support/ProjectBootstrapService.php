<?php

namespace App\Support;

use App\Enums\ProjectHealth;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectRole;
use App\Models\ProjectRolePermission;
use Illuminate\Support\Facades\DB;

final class ProjectBootstrapService
{
    /**
     * @param  array{
     *     name: string,
     *     objective?: string|null,
     *     progress_percent?: int,
     *     next_action?: string|null,
     *     health?: string
     * }  $projectInput
     * @param  list<array{organization_member_id: int, project_role_slug: string}>  $team
     */
    public function create(
        Organization $organization,
        OrganizationMember $creator,
        array $projectInput,
        array $team = [],
    ): Project {
        return DB::transaction(function () use ($organization, $creator, $projectInput, $team): Project {
            $project = Project::query()->create([
                'organization_id' => $organization->id,
                'name' => $projectInput['name'],
                'objective' => $projectInput['objective'] ?? '',
                'progress_percent' => $projectInput['progress_percent'] ?? 0,
                'next_action' => $projectInput['next_action'] ?? null,
                'health' => $projectInput['health'] ?? ProjectHealth::Active->value,
                'owner_member_id' => $creator->id,
                'created_by_member_id' => $creator->id,
            ]);

            $rolesBySlug = [];

            foreach (CommandCentreRoleTemplateRegistry::projectRoles() as $template) {
                $role = ProjectRole::query()->create([
                    'project_id' => $project->id,
                    'name' => $template['name'],
                    'slug' => $template['slug'],
                    'is_default' => $template['is_default'],
                    'sort_order' => $template['sort_order'],
                ]);

                foreach ($template['permissions'] as $permission) {
                    ProjectRolePermission::query()->create([
                        'project_role_id' => $role->id,
                        'permission' => $permission,
                    ]);
                }

                $rolesBySlug[$template['slug']] = $role;
            }

            ProjectMember::query()->create([
                'project_id' => $project->id,
                'organization_member_id' => $creator->id,
                'project_role_id' => $rolesBySlug['project_owner']->id,
                'joined_at' => now(),
            ]);

            foreach ($team as $memberRow) {
                if ((int) $memberRow['organization_member_id'] === $creator->id) {
                    continue;
                }

                $roleSlug = $memberRow['project_role_slug'] ?? 'contributor';
                $role = $rolesBySlug[$roleSlug] ?? $rolesBySlug['contributor'];

                ProjectMember::query()->firstOrCreate(
                    [
                        'project_id' => $project->id,
                        'organization_member_id' => $memberRow['organization_member_id'],
                    ],
                    [
                        'project_role_id' => $role->id,
                        'joined_at' => now(),
                    ],
                );
            }

            return $project->fresh(['roles.permissions', 'members.role']);
        });
    }
}
