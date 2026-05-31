<?php

namespace Tests\Feature\CommandCentre;

use App\Enums\AiSessionContext;
use App\Enums\AiSessionStatus;
use App\Enums\DeliveryStatus;
use App\Enums\ExportJobStatus;
use App\Enums\ExportType;
use App\Enums\NotificationChannel;
use App\Models\ExportJob;
use App\Models\NotificationDelivery;
use App\Models\User;
use App\Support\OrganizationBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_operations_reports(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Reports Org',
        ]);
        $member = $organization->members()->where('user_id', $user->id)->firstOrFail();

        ExportJob::query()->create([
            'organization_id' => $organization->id,
            'requested_by_member_id' => $member->id,
            'export_type' => ExportType::TasksCsv,
            'status' => ExportJobStatus::Completed,
            'disk' => 'local',
            'path' => 'exports/test.csv',
            'expires_at' => now()->addDay(),
            'completed_at' => now(),
        ]);

        NotificationDelivery::query()->create([
            'organization_id' => $organization->id,
            'organization_member_id' => $member->id,
            'recipient_email' => 'fail@example.com',
            'channel' => NotificationChannel::Mail,
            'notification_class' => 'TestMail',
            'event_type' => 'member_invited',
            'subject' => 'Invite failed',
            'status' => DeliveryStatus::Failed,
            'error_message' => 'SMTP timeout',
            'failed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('organizations.reports.index', $organization))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('organizations/settings/reports')
                ->where('exportSummary.completed', 1)
                ->where('deliverySummary.failed_last_7_days', 1)
                ->has('recentFailedDeliveries', 1));
    }

    public function test_member_without_permission_cannot_view_reports(): void
    {
        $owner = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'Restricted Reports Org',
        ]);

        $memberUser = User::factory()->create();
        $memberRole = $organization->roles()->where('slug', 'member')->firstOrFail();
        $organization->members()->create([
            'user_id' => $memberUser->id,
            'organization_role_id' => $memberRole->id,
            'display_name' => 'Regular Member',
            'status' => 'active',
        ]);

        $this->actingAs($memberUser)
            ->get(route('organizations.reports.index', $organization))
            ->assertForbidden();
    }
}
