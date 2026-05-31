<?php

namespace Tests\Feature\CommandCentre;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Support\CommandCentrePermissionRegistry;
use App\Support\CommandCentreRoleTemplateRegistry;
use App\Support\OrganizationBootstrapService;
use App\Support\ProjectBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_create_materializes_four_roles_with_permissions(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Bootstrap Org',
        ]);
        $creator = $organization->members()->where('user_id', $user->id)->firstOrFail();

        $project = app(ProjectBootstrapService::class)->create(
            $organization,
            $creator,
            ['name' => 'Launch Pad'],
        );

        $this->assertDatabaseCount('project_roles', 4);
        $this->assertDatabaseHas('project_members', [
            'project_id' => $project->id,
            'organization_member_id' => $creator->id,
        ]);

        $ownerRole = $project->roles()->where('slug', 'project_owner')->firstOrFail();
        $this->assertCount(
            count(CommandCentrePermissionRegistry::allProjectSlugs()),
            $ownerRole->permissions,
        );
    }

    public function test_project_store_route_bootstraps_team_for_owner(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Route Org',
        ]);

        $this->actingAs($user)
            ->post(route('organizations.projects.store', $organization), [
                'name' => 'Website refresh',
                'objective' => 'Ship the new marketing site',
            ])
            ->assertRedirect();

        $project = Project::query()->where('name', 'Website refresh')->firstOrFail();
        $creator = $organization->members()->where('user_id', $user->id)->firstOrFail();

        $this->assertDatabaseHas('project_members', [
            'project_id' => $project->id,
            'organization_member_id' => $creator->id,
        ]);

        $projectMember = ProjectMember::query()
            ->where('project_id', $project->id)
            ->where('organization_member_id', $creator->id)
            ->with('role')
            ->firstOrFail();

        $this->assertSame('project_owner', $projectMember->role?->slug);
    }

    public function test_project_role_templates_match_registry(): void
    {
        $templates = CommandCentreRoleTemplateRegistry::projectRoles();

        $this->assertCount(4, $templates);
        $this->assertSame(
            ['project_owner', 'project_lead', 'contributor', 'project_viewer'],
            collect($templates)->pluck('slug')->all(),
        );
    }
}
