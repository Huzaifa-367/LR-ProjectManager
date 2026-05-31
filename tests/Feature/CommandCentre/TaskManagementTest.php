<?php

namespace Tests\Feature\CommandCentre;

use App\Enums\TaskKind;
use App\Models\OrganizationMember;
use App\Models\OrganizationRole;
use App\Models\Task;
use App\Models\User;
use App\Support\OrganizationBootstrapService;
use App\Support\ProjectBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_task_with_assignees(): void
    {
        $owner = User::factory()->create();
        $contributorUser = User::factory()->create();

        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'Task Org',
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

        $project = app(ProjectBootstrapService::class)->create(
            $organization,
            $ownerMember,
            ['name' => 'Task Project'],
            [
                [
                    'organization_member_id' => $contributor->id,
                    'project_role_slug' => 'contributor',
                ],
            ],
        );

        $this->actingAs($owner)
            ->post(route('organizations.tasks.store', $organization), [
                'kind' => TaskKind::Task->value,
                'project_id' => $project->id,
                'title' => 'Review vendor quote',
                'assignee_member_ids' => [$contributor->id],
            ])
            ->assertRedirect();

        $task = Task::query()->where('title', 'Review vendor quote')->firstOrFail();

        $this->assertSame($project->id, $task->project_id);
        $this->assertCount(1, $task->assignees);
        $this->assertTrue(
            $task->assignees()->where('organization_members.id', $contributor->id)->exists(),
        );
    }

    public function test_scope_own_member_sees_only_assigned_or_created_tasks(): void
    {
        $owner = User::factory()->create();
        $memberUser = User::factory()->create();

        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'Scope Org',
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

        $project = app(ProjectBootstrapService::class)->create(
            $organization,
            $ownerMember,
            ['name' => 'Scoped Project'],
            [
                [
                    'organization_member_id' => $member->id,
                    'project_role_slug' => 'contributor',
                ],
            ],
        );

        $visibleTask = Task::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'kind' => TaskKind::Task,
            'title' => 'Assigned to member',
            'created_by_member_id' => $ownerMember->id,
            'status' => 'pending',
        ]);
        $visibleTask->assignees()->attach($member->id, [
            'is_primary' => true,
            'assigned_at' => now(),
            'assigned_by_member_id' => $ownerMember->id,
        ]);

        Task::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'kind' => TaskKind::Task,
            'title' => 'Owner only task',
            'created_by_member_id' => $ownerMember->id,
            'status' => 'pending',
        ]);

        $this->actingAs($memberUser)
            ->get(route('organizations.tasks.index', $organization))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('tasks/index')
                ->has('tasks', 1)
                ->where('tasks.0.id', $visibleTask->id));
    }

    public function test_scope_all_owner_sees_all_tasks_in_visible_projects(): void
    {
        $owner = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'All Tasks Org',
        ]);
        $ownerMember = $organization->members()->where('user_id', $owner->id)->firstOrFail();

        $project = app(ProjectBootstrapService::class)->create(
            $organization,
            $ownerMember,
            ['name' => 'All Tasks Project'],
        );

        Task::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'kind' => TaskKind::Task,
            'title' => 'First task',
            'created_by_member_id' => $ownerMember->id,
            'status' => 'pending',
        ]);

        Task::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'kind' => TaskKind::Task,
            'title' => 'Second task',
            'created_by_member_id' => $ownerMember->id,
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->get(route('organizations.tasks.index', $organization))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('tasks/index')
                ->has('tasks', 2));
    }
}
