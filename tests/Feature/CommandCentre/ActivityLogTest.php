<?php

namespace Tests\Feature\CommandCentre;

use App\Models\Task;
use App\Models\User;
use App\Support\OrganizationBootstrapService;
use App\Support\ProjectBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_creation_writes_activity_log(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Audit Org',
        ]);
        $member = $organization->members()->where('user_id', $user->id)->firstOrFail();
        $project = app(ProjectBootstrapService::class)->create(
            $organization,
            $member,
            ['name' => 'Audit Project'],
        );

        $this->actingAs($user)
            ->post(route('organizations.tasks.store', $organization), [
                'kind' => 'task',
                'project_id' => $project->id,
                'title' => 'Audited task',
            ])
            ->assertRedirect();

        $task = Task::query()->where('title', 'Audited task')->firstOrFail();

        $this->assertDatabaseHas('activity_logs', [
            'organization_id' => $organization->id,
            'subject_type' => Task::class,
            'subject_id' => $task->id,
            'event' => 'task.created',
        ]);
    }

    public function test_activity_log_page_loads_for_admin(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Log Viewer Org',
        ]);

        $this->actingAs($user)
            ->get(route('organizations.activity-logs.index', $organization))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('organizations/settings/activity-logs')
                ->has('logs'));
    }
}
