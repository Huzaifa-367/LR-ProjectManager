<?php

namespace Tests\Feature\CommandCentre;

use App\Enums\NotificationChannel;
use App\Enums\NotificationEventType;
use App\Enums\ScheduledNotificationStatus;
use App\Enums\TaskKind;
use App\Mail\TaskAssignedMail;
use App\Mail\TaskDueSoonMail;
use App\Models\MemberNotificationPreference;
use App\Models\OrganizationMember;
use App\Models\OrganizationRole;
use App\Models\ScheduledNotification;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use App\Support\MemberNotificationPreferenceSeeder;
use App\Support\OrganizationBootstrapService;
use App\Support\ProjectBootstrapService;
use App\Support\ScheduleTaskDeadlineReminders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrganizationNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_org_bootstrap_seeds_notification_preferences_for_owner(): void
    {
        $owner = User::factory()->create();

        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'Notify Org',
        ]);

        $ownerMember = $organization->members()->where('user_id', $owner->id)->firstOrFail();

        $expectedCount = count(NotificationEventType::cases()) * count(NotificationChannel::cases());

        $this->assertSame(
            $expectedCount,
            MemberNotificationPreference::query()
                ->where('organization_member_id', $ownerMember->id)
                ->count(),
        );
    }

    public function test_task_assignee_sync_sends_database_notification(): void
    {
        Notification::fake();

        [$organization, $owner, $ownerMember, $contributor, $project] = $this->createOrgWithContributor();

        $task = Task::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'kind' => TaskKind::Task,
            'title' => 'Notify assignee task',
            'created_by_member_id' => $ownerMember->id,
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->put(route('organizations.tasks.assignees.sync', [$organization, $task]), [
                'assignee_member_ids' => [$contributor->id],
            ])
            ->assertRedirect();

        Notification::assertSentTo(
            $contributor->user,
            TaskAssignedNotification::class,
        );
    }

    public function test_task_assignment_respects_disabled_database_preference(): void
    {
        Notification::fake();

        [$organization, $owner, $ownerMember, $contributor, $project] = $this->createOrgWithContributor();

        MemberNotificationPreference::query()->updateOrCreate(
            [
                'organization_member_id' => $contributor->id,
                'event_type' => NotificationEventType::TaskAssigned->value,
                'channel' => NotificationChannel::Database->value,
            ],
            [
                'is_enabled' => false,
            ],
        );

        $task = Task::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'kind' => TaskKind::Task,
            'title' => 'Muted assignee task',
            'created_by_member_id' => $ownerMember->id,
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->put(route('organizations.tasks.assignees.sync', [$organization, $task]), [
                'assignee_member_ids' => [$contributor->id],
            ])
            ->assertRedirect();

        Notification::assertNothingSent();
    }

    public function test_deadline_scheduler_is_idempotent_via_dedupe_key(): void
    {
        [$organization, , $ownerMember, $contributor, $project] = $this->createOrgWithContributor();

        $task = Task::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'kind' => TaskKind::Task,
            'title' => 'Deadline task',
            'created_by_member_id' => $ownerMember->id,
            'status' => 'pending',
            'deadline_date' => now()->addDays(3)->toDateString(),
        ]);
        $task->assignees()->attach($contributor->id, [
            'is_primary' => true,
            'assigned_at' => now(),
            'assigned_by_member_id' => $ownerMember->id,
        ]);

        $scheduler = app(ScheduleTaskDeadlineReminders::class);
        $scheduler->syncForTask($task->fresh(['assignees', 'organization']));
        $scheduler->syncForTask($task->fresh(['assignees', 'organization']));

        $this->assertSame(
            2,
            ScheduledNotification::query()
                ->where('subject_type', Task::class)
                ->where('subject_id', $task->id)
                ->where('status', ScheduledNotificationStatus::Pending)
                ->count(),
        );
    }

    public function test_dispatch_scheduled_command_sends_due_reminder(): void
    {
        Notification::fake();
        Mail::fake();

        [$organization, , $ownerMember, $contributor, $project] = $this->createOrgWithContributor();

        $task = Task::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'kind' => TaskKind::Task,
            'title' => 'Due soon task',
            'created_by_member_id' => $ownerMember->id,
            'status' => 'pending',
            'deadline_date' => now()->addDay()->toDateString(),
        ]);

        ScheduledNotification::query()->create([
            'organization_id' => $organization->id,
            'organization_member_id' => $contributor->id,
            'event_type' => NotificationEventType::TaskDueSoon,
            'channel' => NotificationChannel::Mail,
            'subject_type' => Task::class,
            'subject_id' => $task->id,
            'trigger_at' => now()->subMinute(),
            'payload' => ['task_id' => $task->id],
            'dedupe_key' => 'task:'.$task->id.':due_soon:member:'.$contributor->id.':test',
            'status' => ScheduledNotificationStatus::Pending,
        ]);

        Artisan::call('notifications:dispatch-scheduled');

        Notification::assertSentTo($contributor->user, \App\Notifications\TaskDueSoonNotification::class);
        Mail::assertSent(TaskDueSoonMail::class);

        $this->assertDatabaseHas('scheduled_notifications', [
            'dedupe_key' => 'task:'.$task->id.':due_soon:member:'.$contributor->id.':test',
            'status' => ScheduledNotificationStatus::Sent->value,
        ]);
    }

    public function test_notification_preferences_page_loads(): void
    {
        $owner = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'Preferences Org',
        ]);

        $this->actingAs($owner)
            ->get(route('organizations.notification-preferences.show', $organization))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('organizations/settings/notifications')
                ->has('matrix'));
    }

    /**
     * @return array{
     *     0: \App\Models\Organization,
     *     1: User,
     *     2: OrganizationMember,
     *     3: OrganizationMember,
     *     4: \App\Models\Project
     * }
     */
    private function createOrgWithContributor(): array
    {
        $owner = User::factory()->create();
        $contributorUser = User::factory()->create();

        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'Notification Org',
        ]);

        $ownerMember = $organization->members()->where('user_id', $owner->id)->firstOrFail();
        $memberRole = OrganizationRole::query()
            ->where('organization_id', $organization->id)
            ->where('slug', 'member')
            ->firstOrFail();

        $contributor = OrganizationMember::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $contributorUser->id,
            'organization_role_id' => $memberRole->id,
            'display_name' => $contributorUser->name,
            'email' => $contributorUser->email,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        app(MemberNotificationPreferenceSeeder::class)->seedForMember($contributor);

        $project = app(ProjectBootstrapService::class)->create(
            $organization,
            $ownerMember,
            ['name' => 'Notification Project'],
            [
                [
                    'organization_member_id' => $contributor->id,
                    'project_role_slug' => 'contributor',
                ],
            ],
        );

        return [$organization, $owner, $ownerMember, $contributor, $project];
    }
}
