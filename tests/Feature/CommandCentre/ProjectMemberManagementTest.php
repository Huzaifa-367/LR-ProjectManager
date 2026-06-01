<?php

namespace Tests\Feature\CommandCentre;

use App\Models\OrganizationMember;
use App\Models\ProjectMember;
use App\Models\User;
use App\Support\OrganizationBootstrapService;
use App\Support\ProjectBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProjectMemberManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_team_page_lists_available_organization_members(): void
    {
        $owner = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'Team Org',
        ]);
        $creator = $organization->members()->where('user_id', $owner->id)->firstOrFail();

        $colleague = User::factory()->create();
        $colleagueMember = OrganizationMember::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $colleague->id,
            'organization_role_id' => $organization->roles()->where('slug', 'member')->value('id'),
            'display_name' => 'Colleague',
            'email' => $colleague->email,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $project = app(ProjectBootstrapService::class)->create(
            $organization,
            $creator,
            ['name' => 'Alpha'],
        );

        $this->actingAs($owner)
            ->get(route('projects.members.index', [$organization, $project]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('projects/settings/team')
                ->has('team', 1)
                ->has('roles', 4)
                ->has('availableMembers', 1)
                ->where('availableMembers.0.id', $colleagueMember->id));
    }

    public function test_project_owner_can_add_and_update_team_member_role(): void
    {
        $owner = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'Manage Org',
        ]);
        $creator = $organization->members()->where('user_id', $owner->id)->firstOrFail();

        $colleague = User::factory()->create();
        $colleagueMember = OrganizationMember::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $colleague->id,
            'organization_role_id' => $organization->roles()->where('slug', 'member')->value('id'),
            'display_name' => 'Colleague',
            'email' => $colleague->email,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $project = app(ProjectBootstrapService::class)->create(
            $organization,
            $creator,
            ['name' => 'Beta'],
        );

        $contributorRole = $project->roles()->where('slug', 'contributor')->firstOrFail();
        $leadRole = $project->roles()->where('slug', 'project_lead')->firstOrFail();

        $this->actingAs($owner)
            ->post(route('projects.members.store', [$organization, $project]), [
                'organization_member_id' => $colleagueMember->id,
                'project_role_id' => $contributorRole->id,
            ])
            ->assertRedirect();

        $projectMember = ProjectMember::query()
            ->where('project_id', $project->id)
            ->where('organization_member_id', $colleagueMember->id)
            ->firstOrFail();

        $this->assertSame($contributorRole->id, $projectMember->project_role_id);

        $this->actingAs($owner)
            ->patch(route('projects.members.update', [$organization, $project, $projectMember]), [
                'project_role_id' => $leadRole->id,
            ])
            ->assertRedirect();

        $this->assertSame(
            $leadRole->id,
            $projectMember->fresh()->project_role_id,
        );
    }

    public function test_project_owner_can_remove_non_owner_team_member(): void
    {
        $owner = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'Remove Org',
        ]);
        $creator = $organization->members()->where('user_id', $owner->id)->firstOrFail();

        $colleague = User::factory()->create();
        $colleagueMember = OrganizationMember::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $colleague->id,
            'organization_role_id' => $organization->roles()->where('slug', 'member')->value('id'),
            'display_name' => 'Colleague',
            'email' => $colleague->email,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $project = app(ProjectBootstrapService::class)->create(
            $organization,
            $creator,
            ['name' => 'Gamma'],
        );

        $contributorRole = $project->roles()->where('slug', 'contributor')->firstOrFail();

        ProjectMember::query()->create([
            'project_id' => $project->id,
            'organization_member_id' => $colleagueMember->id,
            'project_role_id' => $contributorRole->id,
            'joined_at' => now(),
        ]);

        $projectMember = ProjectMember::query()
            ->where('project_id', $project->id)
            ->where('organization_member_id', $colleagueMember->id)
            ->firstOrFail();

        $this->actingAs($owner)
            ->delete(route('projects.members.destroy', [$organization, $project, $projectMember]))
            ->assertRedirect();

        $this->assertDatabaseMissing('project_members', [
            'id' => $projectMember->id,
        ]);
    }
}
