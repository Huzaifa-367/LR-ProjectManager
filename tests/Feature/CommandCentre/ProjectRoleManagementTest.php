<?php

namespace Tests\Feature\CommandCentre;

use App\Models\ProjectRole;
use App\Models\User;
use App\Support\OrganizationBootstrapService;
use App\Support\ProjectBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProjectRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_owner_can_create_update_and_delete_custom_project_role(): void
    {
        $owner = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'Project Roles Org',
        ]);
        $creator = $organization->members()->where('user_id', $owner->id)->firstOrFail();

        $project = app(ProjectBootstrapService::class)->create(
            $organization,
            $creator,
            ['name' => 'Roles Project'],
        );

        $this->actingAs($owner)
            ->post(route('projects.roles.store', [$organization, $project]), [
                'name' => 'QA lead',
                'permissions' => [
                    'project.tasks.index',
                    'project.tasks.show',
                ],
            ])
            ->assertRedirect();

        $customRole = ProjectRole::query()
            ->where('project_id', $project->id)
            ->where('slug', 'qa-lead')
            ->firstOrFail();

        $this->assertFalse($customRole->is_default);

        $this->actingAs($owner)
            ->patch(route('projects.roles.update', [$organization, $project, $customRole]), [
                'name' => 'QA director',
            ])
            ->assertRedirect();

        $this->assertSame('QA director', $customRole->fresh()->name);

        $this->actingAs($owner)
            ->delete(route('projects.roles.destroy', [$organization, $project, $customRole]))
            ->assertRedirect(route('projects.roles.index', [$organization, $project]));

        $this->assertDatabaseMissing('project_roles', ['id' => $customRole->id]);
    }

    public function test_project_roles_index_renders_manage_ui_props(): void
    {
        $owner = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'Roles UI Org',
        ]);
        $creator = $organization->members()->where('user_id', $owner->id)->firstOrFail();

        $project = app(ProjectBootstrapService::class)->create(
            $organization,
            $creator,
            ['name' => 'UI Project'],
        );

        $this->actingAs($owner)
            ->get(route('projects.roles.index', [$organization, $project]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('projects/settings/roles/index')
                ->has('roles', 4)
                ->has('permissionGroups'));
    }

    public function test_system_project_role_cannot_be_deleted(): void
    {
        $owner = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'System Role Org',
        ]);
        $creator = $organization->members()->where('user_id', $owner->id)->firstOrFail();

        $project = app(ProjectBootstrapService::class)->create(
            $organization,
            $creator,
            ['name' => 'Protected'],
        );

        $contributorRole = $project->roles()->where('slug', 'contributor')->firstOrFail();

        $this->actingAs($owner)
            ->delete(route('projects.roles.destroy', [$organization, $project, $contributorRole]))
            ->assertForbidden();
    }
}
