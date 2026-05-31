<?php

namespace Tests\Feature\CommandCentre;

use App\Models\User;
use App\Support\OrganizationBootstrapService;
use App\Support\ProjectBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_context_includes_accessible_projects(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Project Context Org',
        ]);
        $member = $organization->members()->where('user_id', $user->id)->firstOrFail();

        app(ProjectBootstrapService::class)->create(
            $organization,
            $member,
            ['name' => 'Alpha Project'],
        );

        $this->actingAs($user)
            ->get(route('organizations.command-centre.index', $organization))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('organizationContext.projects', 1)
                ->where('organizationContext.selectedProject', null));
    }

    public function test_member_can_select_project_in_header_context(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Selectable Project Org',
        ]);
        $member = $organization->members()->where('user_id', $user->id)->firstOrFail();

        $project = app(ProjectBootstrapService::class)->create(
            $organization,
            $member,
            ['name' => 'Filtered Project'],
        );

        $this->actingAs($user)
            ->post(route('organizations.select-project', $organization), [
                'project_id' => $project->id,
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->get(route('organizations.command-centre.index', $organization))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('organizationContext.selectedProject.id', $project->id)
                ->where('filters.project_id', $project->id));
    }

    public function test_member_can_clear_selected_project(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Clear Project Org',
        ]);
        $member = $organization->members()->where('user_id', $user->id)->firstOrFail();

        $project = app(ProjectBootstrapService::class)->create(
            $organization,
            $member,
            ['name' => 'Temporary Filter'],
        );

        $this->actingAs($user)
            ->post(route('organizations.select-project', $organization), [
                'project_id' => $project->id,
            ]);

        $this->actingAs($user)
            ->post(route('organizations.select-project', $organization), [
                'project_id' => null,
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->get(route('organizations.command-centre.index', $organization))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('organizationContext.selectedProject', null)
                ->where('filters.project_id', null));
    }
}
