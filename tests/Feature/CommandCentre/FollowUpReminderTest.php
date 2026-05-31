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

class FollowUpReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_follow_up_status_creates_linked_reminder_task(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Follow Up Org',
        ]);
        $member = $organization->members()->where('user_id', $user->id)->firstOrFail();
        $project = app(ProjectBootstrapService::class)->create(
            $organization,
            $member,
            ['name' => 'Follow Up Project'],
        );

        $task = Task::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'kind' => TaskKind::Task,
            'title' => 'Needs follow up',
            'created_by_member_id' => $member->id,
            'status' => TaskStatus::Pending,
        ]);

        $this->actingAs($user)
            ->patch(route('organizations.tasks.status.update', [$organization, $task]), [
                'status' => TaskStatus::FollowUp->value,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'kind' => TaskKind::Reminder->value,
        ]);

        $reminder = Task::query()
            ->where('kind', TaskKind::Reminder)
            ->where('meta->source_task_id', $task->id)
            ->first();

        $this->assertNotNull($reminder);
        $this->assertStringContainsString('Needs follow up', $reminder->title);
    }

    public function test_follow_up_does_not_create_duplicate_reminders(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Duplicate Reminder Org',
        ]);
        $member = $organization->members()->where('user_id', $user->id)->firstOrFail();
        $project = app(ProjectBootstrapService::class)->create(
            $organization,
            $member,
            ['name' => 'Duplicate Project'],
        );

        $task = Task::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'kind' => TaskKind::Task,
            'title' => 'Repeat follow up',
            'created_by_member_id' => $member->id,
            'status' => TaskStatus::FollowUp,
        ]);

        Task::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'kind' => TaskKind::Reminder,
            'title' => 'Follow-up: Repeat follow up',
            'created_by_member_id' => $member->id,
            'status' => TaskStatus::Pending,
            'meta' => ['source_task_id' => $task->id],
        ]);

        $this->actingAs($user)
            ->patch(route('organizations.tasks.status.update', [$organization, $task]), [
                'status' => TaskStatus::FollowUp->value,
            ])
            ->assertRedirect();

        $this->assertSame(
            1,
            Task::query()
                ->where('kind', TaskKind::Reminder)
                ->where('meta->source_task_id', $task->id)
                ->count(),
        );
    }
}
