<?php

namespace Tests\Feature\CommandCentre;

use App\Models\Attachment;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use App\Support\OrganizationBootstrapService;
use App\Support\ProjectBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TaskCommentsAndAttachmentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_add_comment_on_visible_task(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Comment Org',
        ]);
        $member = $organization->members()->where('user_id', $user->id)->firstOrFail();
        $project = app(ProjectBootstrapService::class)->create(
            $organization,
            $member,
            ['name' => 'Comment Project'],
        );

        $task = Task::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'kind' => 'task',
            'title' => 'Discuss me',
            'created_by_member_id' => $member->id,
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->post(route('organizations.tasks.comments.store', [$organization, $task]), [
                'body' => 'First comment on this task.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('task_comments', [
            'task_id' => $task->id,
            'organization_member_id' => $member->id,
            'body' => 'First comment on this task.',
        ]);
    }

    public function test_member_cannot_edit_another_members_comment(): void
    {
        $owner = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'Edit Comment Org',
        ]);
        $ownerMember = $organization->members()->where('user_id', $owner->id)->firstOrFail();
        $project = app(ProjectBootstrapService::class)->create(
            $organization,
            $ownerMember,
            ['name' => 'Edit Project'],
        );

        $otherUser = User::factory()->create();
        $otherMember = $organization->members()->create([
            'user_id' => $otherUser->id,
            'organization_role_id' => $organization->roles()->where('slug', 'member')->firstOrFail()->id,
            'display_name' => 'Other Member',
            'email' => $otherUser->email,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $task = Task::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'kind' => 'task',
            'title' => 'Shared task',
            'created_by_member_id' => $ownerMember->id,
            'status' => 'pending',
        ]);

        $comment = TaskComment::query()->create([
            'task_id' => $task->id,
            'organization_member_id' => $ownerMember->id,
            'body' => 'Owner comment',
        ]);

        $this->actingAs($otherUser)
            ->patch(route('organizations.tasks.comments.update', [$organization, $task, $comment]), [
                'body' => 'Hijacked comment',
            ])
            ->assertForbidden();
    }

    public function test_member_can_upload_and_delete_own_attachment(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Attachment Org',
        ]);
        $member = $organization->members()->where('user_id', $user->id)->firstOrFail();
        $project = app(ProjectBootstrapService::class)->create(
            $organization,
            $member,
            ['name' => 'Attachment Project'],
        );

        $task = Task::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'kind' => 'task',
            'title' => 'File task',
            'created_by_member_id' => $member->id,
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->post(route('organizations.attachments.store', $organization), [
                'attachable_type' => 'task',
                'attachable_id' => $task->id,
                'file' => UploadedFile::fake()->create('brief.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect();

        $attachment = Attachment::query()->where('organization_id', $organization->id)->firstOrFail();
        Storage::disk($attachment->disk)->assertExists($attachment->path);

        $this->actingAs($user)
            ->delete(route('organizations.attachments.destroy', [$organization, $attachment]))
            ->assertRedirect();

        $this->assertSoftDeleted('attachments', ['id' => $attachment->id]);
    }

    public function test_task_show_includes_comments_and_attachments(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Show Task Org',
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
            'kind' => 'task',
            'title' => 'Detail task',
            'created_by_member_id' => $member->id,
            'status' => 'pending',
        ]);

        TaskComment::query()->create([
            'task_id' => $task->id,
            'organization_member_id' => $member->id,
            'body' => 'Visible comment',
        ]);

        $this->actingAs($user)
            ->get(route('organizations.tasks.show', [$organization, $task]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('tasks/show')
                ->has('comments', 1)
                ->has('attachments'));
    }
}
