<?php

namespace Tests\Feature\CommandCentre;

use App\Enums\AiMessageRole;
use App\Enums\AiSessionContext;
use App\Enums\AiSessionStatus;
use App\Models\ActivityLog;
use App\Models\AiAuditLog;
use App\Models\AiMessage;
use App\Models\AiSession;
use App\Models\User;
use App\Support\OrganizationBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditRetentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_purge_ai_audit_logs_removes_stale_rows(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'AI Audit Org',
        ]);
        $member = $organization->members()->where('user_id', $user->id)->firstOrFail();

        $session = AiSession::query()->create([
            'organization_id' => $organization->id,
            'organization_member_id' => $member->id,
            'user_id' => $user->id,
            'context' => AiSessionContext::ProjectOnboarding,
            'status' => AiSessionStatus::Active,
        ]);

        $message = AiMessage::query()->create([
            'ai_session_id' => $session->id,
            'role' => AiMessageRole::Assistant,
            'content' => 'Hello',
        ]);

        AiAuditLog::query()->create([
            'ai_message_id' => $message->id,
            'organization_id' => $organization->id,
            'tool_name' => 'list_projects',
            'tool_input' => ['query' => 'stale'],
            'created_at' => now()->subDays(120),
        ]);

        AiAuditLog::query()->create([
            'ai_message_id' => $message->id,
            'organization_id' => $organization->id,
            'tool_name' => 'recent_call',
            'tool_input' => ['query' => 'recent'],
            'created_at' => now()->subDay(),
        ]);

        $this->artisan('audit:purge-ai-logs')->assertSuccessful();

        $this->assertDatabaseCount('ai_audit_logs', 1);
        $this->assertDatabaseHas('ai_audit_logs', [
            'tool_name' => 'recent_call',
        ]);
    }

    public function test_purge_activity_logs_removes_stale_rows(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Activity Retention Org',
        ]);
        $member = $organization->members()->where('user_id', $user->id)->firstOrFail();

        ActivityLog::query()->create([
            'organization_id' => $organization->id,
            'actor_member_id' => $member->id,
            'event' => 'old.event',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'created_at' => now()->subDays(400),
        ]);

        ActivityLog::query()->create([
            'organization_id' => $organization->id,
            'actor_member_id' => $member->id,
            'event' => 'recent.event',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'created_at' => now()->subDay(),
        ]);

        $this->artisan('audit:purge-activity-logs')->assertSuccessful();

        $this->assertDatabaseCount('activity_logs', 1);
        $this->assertDatabaseHas('activity_logs', [
            'event' => 'recent.event',
        ]);
    }
}
