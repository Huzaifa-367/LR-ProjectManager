<?php

namespace Tests\Feature\CommandCentre;

use App\Enums\TaskKind;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use App\Support\OrganizationBootstrapService;
use App\Support\ProjectBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_update_to_done_marks_task_complete_and_updates_project_progress(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Progress Org',
        ]);
        $member = $organization->members()->where('user_id', $user->id)->firstOrFail();
        $project = app(ProjectBootstrapService::class)->create(
            $organization,
            $member,
            ['name' => 'Progress Project', 'progress_percent' => 0],
        );

        $first = Task::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'kind' => TaskKind::Task,
            'title' => 'First task',
            'created_by_member_id' => $member->id,
            'status' => TaskStatus::Pending,
        ]);

        Task::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'kind' => TaskKind::Task,
            'title' => 'Second task',
            'created_by_member_id' => $member->id,
            'status' => TaskStatus::Pending,
        ]);

        $this->actingAs($user)
            ->patch(route('organizations.tasks.status.update', [$organization, $first]), [
                'status' => TaskStatus::Done->value,
            ])
            ->assertRedirect();

        $first->refresh();
        $project->refresh();

        $this->assertTrue($first->is_done);
        $this->assertSame(TaskStatus::Done, $first->status);
        $this->assertSame(50, $project->progress_percent);
        $this->assertSame('Second task', $project->next_action);
    }

    public function test_toggle_done_updates_project_progress(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Toggle Org',
        ]);
        $member = $organization->members()->where('user_id', $user->id)->firstOrFail();
        $project = app(ProjectBootstrapService::class)->create(
            $organization,
            $member,
            ['name' => 'Toggle Project'],
        );

        $task = Task::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'kind' => TaskKind::Task,
            'title' => 'Only task',
            'created_by_member_id' => $member->id,
            'status' => TaskStatus::Pending,
        ]);

        $this->actingAs($user)
            ->patch(route('organizations.tasks.toggle-done', [$organization, $task]))
            ->assertRedirect();

        $project->refresh();

        $this->assertSame(100, $project->progress_percent);
        $this->assertNull($project->next_action);
    }

    public function test_task_update_with_done_status_syncs_completion_fields(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Edit Org',
        ]);
        $member = $organization->members()->where('user_id', $user->id)->firstOrFail();
        $project = app(ProjectBootstrapService::class)->create(
            $organization,
            $member,
            ['name' => 'Edit Project'],
        );

        $task = Task::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'kind' => TaskKind::Task,
            'title' => 'Editable task',
            'created_by_member_id' => $member->id,
            'status' => TaskStatus::InProgress,
        ]);

        $this->actingAs($user)
            ->patch(route('organizations.tasks.update', [$organization, $task]), [
                'status' => TaskStatus::Done->value,
            ])
            ->assertRedirect();

        $task->refresh();
        $project->refresh();

        $this->assertTrue($task->is_done);
        $this->assertSame(100, $project->progress_percent);
    }

    public function test_decisions_and_reminders_do_not_affect_project_progress(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Kinds Org',
        ]);
        $member = $organization->members()->where('user_id', $user->id)->firstOrFail();
        $project = app(ProjectBootstrapService::class)->create(
            $organization,
            $member,
            ['name' => 'Kinds Project'],
        );

        Task::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'kind' => TaskKind::Decision,
            'title' => 'Approve budget',
            'created_by_member_id' => $member->id,
            'status' => TaskStatus::Pending,
            'is_done' => true,
        ]);

        Task::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'kind' => TaskKind::Task,
            'title' => 'Build feature',
            'created_by_member_id' => $member->id,
            'status' => TaskStatus::Pending,
        ]);

        $project->refresh();

        $this->assertSame(0, $project->progress_percent);
        $this->assertSame('Build feature', $project->next_action);
    }

    public function test_project_show_reflects_updated_progress_after_kanban_status_change(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Show Org',
        ]);
        $member = $organization->members()->where('user_id', $user->id)->firstOrFail();
        $project = app(ProjectBootstrapService::class)->create(
            $organization,
            $member,
            ['name' => 'Show Project'],
        );

        $task = Task::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'kind' => TaskKind::Task,
            'title' => 'Ship release',
            'created_by_member_id' => $member->id,
            'status' => TaskStatus::Pending,
        ]);

        $this->actingAs($user)
            ->patch(route('organizations.tasks.status.update', [$organization, $task]), [
                'status' => TaskStatus::Done->value,
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->get(route('organizations.projects.show', [$organization, $project]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('organizations/projects/show')
                ->where('project.progress_percent', 100)
                ->where('taskSummary.done', 1)
                ->where('taskSummary.open', 0));
    }
}
