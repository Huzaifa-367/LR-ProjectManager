<?php

namespace Tests\Feature\CommandCentre;

use App\Enums\TaskKind;
use App\Models\Task;
use App\Models\User;
use App\Support\OrganizationBootstrapService;
use App\Support\ProjectBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommandCentreDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_centre_loads_dashboard_props_for_owner(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Dashboard Org',
        ]);
        $ownerMember = $organization->members()->where('user_id', $user->id)->firstOrFail();

        $project = app(ProjectBootstrapService::class)->create(
            $organization,
            $ownerMember,
            ['name' => 'Dashboard Project'],
        );

        Task::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'kind' => TaskKind::Task,
            'title' => 'Open dashboard task',
            'created_by_member_id' => $ownerMember->id,
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->get(route('organizations.command-centre.index', $organization))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('command-centre/index')
                ->has('stats')
                ->where('stats.open_tasks', 1)
                ->where('stats.projects', 1)
                ->has('focusPins')
                ->has('tasks', 1)
                ->has('projects', 1));
    }

    public function test_stats_done_today_reflects_completed_tasks(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Stats Org',
        ]);
        $ownerMember = $organization->members()->where('user_id', $user->id)->firstOrFail();
        $project = app(ProjectBootstrapService::class)->create(
            $organization,
            $ownerMember,
            ['name' => 'Stats Project'],
        );

        Task::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'kind' => TaskKind::Task,
            'title' => 'Completed today',
            'created_by_member_id' => $ownerMember->id,
            'status' => 'done',
            'is_done' => true,
            'completed_at' => now(),
            'completed_by_member_id' => $ownerMember->id,
        ]);

        $this->actingAs($user)
            ->get(route('organizations.command-centre.index', $organization))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('stats.done_today', 1));
    }
}
