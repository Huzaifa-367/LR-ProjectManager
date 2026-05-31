<?php

namespace Tests\Feature\CommandCentre;

use App\Enums\ExportJobStatus;
use App\Enums\ExportType;
use App\Enums\TaskKind;
use App\Models\ExportJob;
use App\Models\Task;
use App\Models\User;
use App\Support\OrganizationBootstrapService;
use App\Support\ProjectBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExportJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_request_tasks_csv_export_and_download_file(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'Export Org',
        ]);
        $ownerMember = $organization->members()->where('user_id', $owner->id)->firstOrFail();

        $project = app(ProjectBootstrapService::class)->create(
            $organization,
            $ownerMember,
            ['name' => 'Export Project'],
        );

        Task::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'kind' => TaskKind::Task,
            'title' => 'Export me',
            'created_by_member_id' => $ownerMember->id,
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->post(route('organizations.exports.store', $organization), [
                'export_type' => ExportType::TasksCsv->value,
            ])
            ->assertRedirect();

        $exportJob = ExportJob::query()->firstOrFail();

        $this->assertSame(ExportJobStatus::Completed, $exportJob->status);
        $this->assertNotNull($exportJob->path);
        Storage::disk('local')->assertExists((string) $exportJob->path);

        $this->actingAs($owner)
            ->get(route('organizations.exports.show', [$organization, $exportJob]))
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_export_respects_task_visibility_scope(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create();
        $memberUser = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'Scoped Export Org',
        ]);
        $ownerMember = $organization->members()->where('user_id', $owner->id)->firstOrFail();
        $memberRole = $organization->roles()->where('slug', 'member')->firstOrFail();

        $member = \App\Models\OrganizationMember::query()->create([
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
            'title' => 'Assigned task',
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
            'title' => 'Hidden task',
            'created_by_member_id' => $ownerMember->id,
            'status' => 'pending',
        ]);

        $this->actingAs($memberUser)
            ->post(route('organizations.exports.store', $organization), [
                'export_type' => ExportType::TasksCsv->value,
            ])
            ->assertRedirect();

        $exportJob = ExportJob::query()->firstOrFail();
        $csv = Storage::disk('local')->get((string) $exportJob->path);

        $this->assertStringContainsString('Assigned task', (string) $csv);
        $this->assertStringNotContainsString('Hidden task', (string) $csv);
    }

    public function test_purge_expired_exports_removes_files_and_rows(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'Purge Export Org',
        ]);
        $ownerMember = $organization->members()->where('user_id', $owner->id)->firstOrFail();

        $path = 'exports/organization-1/export-1-tasks.csv';
        Storage::disk('local')->put($path, 'id,title');

        ExportJob::query()->create([
            'organization_id' => $organization->id,
            'requested_by_member_id' => $ownerMember->id,
            'export_type' => ExportType::TasksCsv,
            'filters' => null,
            'status' => ExportJobStatus::Completed,
            'disk' => 'local',
            'path' => $path,
            'expires_at' => now()->subMinute(),
            'completed_at' => now()->subHour(),
        ]);

        $this->artisan('exports:purge-expired')->assertSuccessful();

        Storage::disk('local')->assertMissing($path);
        $this->assertDatabaseCount('export_jobs', 0);
    }
}
