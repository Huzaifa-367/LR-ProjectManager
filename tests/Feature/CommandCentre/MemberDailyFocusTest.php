<?php

namespace Tests\Feature\CommandCentre;

use App\Enums\DeadlineType;
use App\Enums\TaskKind;
use App\Models\MemberDailyFocus;
use App\Models\Task;
use App\Models\User;
use App\Support\OrganizationBootstrapService;
use App\Support\ProjectBootstrapService;
use App\Support\SyncMemberDailyFocus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberDailyFocusTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_pin_visible_task_to_focus(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Focus Org',
        ]);
        $member = $organization->members()->where('user_id', $user->id)->firstOrFail();
        $project = app(ProjectBootstrapService::class)->create(
            $organization,
            $member,
            ['name' => 'Focus Project'],
        );

        $task = Task::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'kind' => TaskKind::Task,
            'title' => 'Pin me',
            'created_by_member_id' => $member->id,
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->post(route('organizations.focus.store', $organization), [
                'task_id' => $task->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('member_daily_focus', [
            'organization_member_id' => $member->id,
            'task_id' => $task->id,
            'is_auto' => false,
        ]);
    }

    public function test_focus_cap_is_enforced_at_ten_pins(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Cap Org',
        ]);
        $organization->update([
            'settings' => array_merge(
                $organization->settings ?? [],
                ['focus_cap' => 10],
            ),
        ]);
        $member = $organization->members()->where('user_id', $user->id)->firstOrFail();
        $project = app(ProjectBootstrapService::class)->create(
            $organization,
            $member,
            ['name' => 'Cap Project'],
        );

        for ($index = 0; $index < 10; $index++) {
            $task = Task::query()->create([
                'organization_id' => $organization->id,
                'project_id' => $project->id,
                'kind' => TaskKind::Task,
                'title' => "Task {$index}",
                'created_by_member_id' => $member->id,
                'status' => 'pending',
            ]);

            MemberDailyFocus::query()->create([
                'organization_member_id' => $member->id,
                'task_id' => $task->id,
                'focus_date' => now()->toDateString(),
                'sort_order' => $index + 1,
                'is_auto' => false,
            ]);
        }

        $extraTask = Task::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'kind' => TaskKind::Task,
            'title' => 'Overflow task',
            'created_by_member_id' => $member->id,
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->post(route('organizations.focus.store', $organization), [
                'task_id' => $extraTask->id,
            ])
            ->assertSessionHasErrors('task_id');
    }

    public function test_auto_sync_creates_focus_for_today_deadline_tasks(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Auto Focus Org',
        ]);
        $member = $organization->members()->where('user_id', $user->id)->firstOrFail();
        $project = app(ProjectBootstrapService::class)->create(
            $organization,
            $member,
            ['name' => 'Auto Project'],
        );

        $task = Task::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'kind' => TaskKind::Task,
            'title' => 'Due today',
            'created_by_member_id' => $member->id,
            'status' => 'pending',
            'deadline_type' => DeadlineType::Today,
        ]);
        $task->assignees()->attach($member->id, [
            'is_primary' => true,
            'assigned_at' => now(),
            'assigned_by_member_id' => $member->id,
        ]);

        app(SyncMemberDailyFocus::class)->syncForTask($task->fresh(['assignees']));

        $this->assertDatabaseHas('member_daily_focus', [
            'organization_member_id' => $member->id,
            'task_id' => $task->id,
            'is_auto' => true,
        ]);
    }
}
