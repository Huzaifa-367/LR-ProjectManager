<?php

namespace Tests\Feature\CommandCentre;

use App\Models\OrganizationMember;
use App\Models\OrganizationRole;
use App\Models\Project;
use App\Models\User;
use App\Support\OrganizationBootstrapService;
use App\Support\ProjectBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_with_scope_member_sees_only_joined_projects(): void
    {
        $owner = User::factory()->create();
        $memberUser = User::factory()->create();

        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'Visibility Org',
        ]);

        $ownerMember = $organization->members()->where('user_id', $owner->id)->firstOrFail();
        $memberRole = OrganizationRole::query()
            ->where('organization_id', $organization->id)
            ->where('slug', 'member')
            ->firstOrFail();

        $member = OrganizationMember::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $memberUser->id,
            'organization_role_id' => $memberRole->id,
            'display_name' => $memberUser->name,
            'email' => $memberUser->email,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $visibleProject = app(ProjectBootstrapService::class)->create(
            $organization,
            $ownerMember,
            ['name' => 'Visible Project'],
            [
                [
                    'organization_member_id' => $member->id,
                    'project_role_slug' => 'contributor',
                ],
            ],
        );

        app(ProjectBootstrapService::class)->create(
            $organization,
            $ownerMember,
            ['name' => 'Hidden Project'],
        );

        $this->actingAs($memberUser)
            ->get(route('organizations.projects.index', $organization))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('organizations/projects/index')
                ->has('projects', 1)
                ->where('projects.0.id', $visibleProject->id));

        $this->actingAs($memberUser)
            ->get(route('organizations.projects.show', [$organization, $visibleProject]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('tasks')
                ->has('team')
                ->has('taskSummary'));

        $hiddenProject = Project::query()
            ->where('organization_id', $organization->id)
            ->where('name', 'Hidden Project')
            ->firstOrFail();

        $this->actingAs($memberUser)
            ->get(route('organizations.projects.show', [$organization, $hiddenProject]))
            ->assertNotFound();
    }

    public function test_non_member_cannot_access_organization_projects(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'Private Projects Org',
        ]);

        $this->actingAs($stranger)
            ->get(route('organizations.projects.index', $organization))
            ->assertForbidden();
    }
}
