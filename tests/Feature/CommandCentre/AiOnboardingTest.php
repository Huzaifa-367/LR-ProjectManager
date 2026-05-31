<?php

namespace Tests\Feature\CommandCentre;

use App\Enums\AiSessionContext;
use App\Enums\AiSessionStatus;
use App\Enums\OnboardingProposalStatus;
use App\Enums\TaskKind;
use App\Models\AiOnboardingProposal;
use App\Models\AiSession;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\OrganizationBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_onboarding_wizard_loads_for_owner(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'AI Org',
        ]);

        $this->actingAs($user)
            ->get(route('organizations.projects.onboarding', $organization))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('organizations/projects/onboarding')
                ->has('session')
                ->has('members'));
    }

    public function test_propose_creates_pending_review_proposal_without_mutating_tasks(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Propose Org',
        ]);
        $member = $organization->members()->where('user_id', $user->id)->firstOrFail();

        $session = AiSession::query()->create([
            'organization_id' => $organization->id,
            'organization_member_id' => $member->id,
            'user_id' => $user->id,
            'context' => AiSessionContext::ProjectOnboarding,
            'status' => AiSessionStatus::Active,
        ]);

        $taskCountBefore = Task::query()->count();
        $projectCountBefore = Project::query()->count();

        $this->actingAs($user)
            ->post(route('organizations.ai-onboarding.propose', $organization), [
                'ai_session_id' => $session->id,
                'brief' => 'Launch TAP in three new markets by Q4',
                'team' => [[
                    'organization_member_id' => $member->id,
                    'project_role_slug' => 'project_lead',
                ]],
            ])
            ->assertRedirect();

        $this->assertSame($taskCountBefore, Task::query()->count());
        $this->assertSame($projectCountBefore, Project::query()->count());

        $this->assertDatabaseHas('ai_onboarding_proposals', [
            'ai_session_id' => $session->id,
            'organization_id' => $organization->id,
            'status' => OnboardingProposalStatus::PendingReview->value,
        ]);
    }

    public function test_apply_creates_project_and_tasks_in_one_flow(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Apply Org',
        ]);
        $member = $organization->members()->where('user_id', $user->id)->firstOrFail();

        $session = AiSession::query()->create([
            'organization_id' => $organization->id,
            'organization_member_id' => $member->id,
            'user_id' => $user->id,
            'context' => AiSessionContext::ProjectOnboarding,
            'status' => AiSessionStatus::Active,
        ]);

        $proposal = AiOnboardingProposal::query()->create([
            'ai_session_id' => $session->id,
            'organization_id' => $organization->id,
            'created_by_member_id' => $member->id,
            'proposal_type' => 'project',
            'status' => OnboardingProposalStatus::Approved,
            'payload' => [
                'project' => [
                    'name' => 'Applied Project',
                    'objective' => 'Test apply flow',
                    'health' => 'active',
                    'progress_percent' => 0,
                ],
                'team' => [[
                    'organization_member_id' => $member->id,
                    'project_role_slug' => 'project_lead',
                ]],
                'tasks' => [[
                    'title' => 'First milestone',
                    'status' => 'pending',
                    'assignee_member_ids' => [$member->id],
                    'kind' => 'task',
                ]],
                'decisions' => [],
                'reminders' => [],
            ],
            'version' => 1,
        ]);

        $this->actingAs($user)
            ->post(route('organizations.ai-onboarding.apply', [$organization, $proposal]))
            ->assertRedirect();

        $this->assertDatabaseHas('projects', [
            'organization_id' => $organization->id,
            'name' => 'Applied Project',
        ]);

        $this->assertDatabaseHas('tasks', [
            'organization_id' => $organization->id,
            'kind' => TaskKind::Task->value,
            'title' => 'First milestone',
        ]);

        $proposal->refresh();
        $this->assertSame(OnboardingProposalStatus::Applied, $proposal->status);
        $this->assertNotNull($proposal->project_id);
    }

    public function test_reject_marks_proposal_rejected(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Reject Org',
        ]);
        $member = $organization->members()->where('user_id', $user->id)->firstOrFail();

        $session = AiSession::query()->create([
            'organization_id' => $organization->id,
            'organization_member_id' => $member->id,
            'user_id' => $user->id,
            'context' => AiSessionContext::ProjectOnboarding,
            'status' => AiSessionStatus::Active,
        ]);

        $proposal = AiOnboardingProposal::query()->create([
            'ai_session_id' => $session->id,
            'organization_id' => $organization->id,
            'created_by_member_id' => $member->id,
            'proposal_type' => 'project',
            'status' => OnboardingProposalStatus::PendingReview,
            'payload' => [
                'project' => ['name' => 'Rejected Project'],
                'team' => [],
                'tasks' => [],
                'decisions' => [],
                'reminders' => [],
            ],
            'version' => 1,
        ]);

        $this->actingAs($user)
            ->patch(route('organizations.ai-onboarding.reject', [$organization, $proposal]), [
                'rejection_reason' => 'Not aligned with strategy',
            ])
            ->assertRedirect(route('organizations.projects.onboarding', $organization));

        $this->assertDatabaseHas('ai_onboarding_proposals', [
            'id' => $proposal->id,
            'status' => OnboardingProposalStatus::Rejected->value,
        ]);
    }

    public function test_regenerate_supersedes_pending_review_proposal(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Supersede Org',
        ]);
        $member = $organization->members()->where('user_id', $user->id)->firstOrFail();

        $session = AiSession::query()->create([
            'organization_id' => $organization->id,
            'organization_member_id' => $member->id,
            'user_id' => $user->id,
            'context' => AiSessionContext::ProjectOnboarding,
            'status' => AiSessionStatus::Active,
        ]);

        $first = AiOnboardingProposal::query()->create([
            'ai_session_id' => $session->id,
            'organization_id' => $organization->id,
            'created_by_member_id' => $member->id,
            'proposal_type' => 'project',
            'status' => OnboardingProposalStatus::PendingReview,
            'payload' => [
                'project' => ['name' => 'Version 1'],
                'team' => [[
                    'organization_member_id' => $member->id,
                    'project_role_slug' => 'project_lead',
                ]],
                'tasks' => [],
                'decisions' => [],
                'reminders' => [],
            ],
            'version' => 1,
        ]);

        $this->actingAs($user)
            ->post(route('organizations.ai-onboarding.propose', $organization), [
                'ai_session_id' => $session->id,
                'brief' => 'Updated brief for version 2',
                'team' => [[
                    'organization_member_id' => $member->id,
                    'project_role_slug' => 'project_lead',
                ]],
            ])
            ->assertRedirect();

        $first->refresh();
        $this->assertSame(OnboardingProposalStatus::Superseded, $first->status);

        $this->assertDatabaseHas('ai_onboarding_proposals', [
            'ai_session_id' => $session->id,
            'status' => OnboardingProposalStatus::PendingReview->value,
            'version' => 2,
        ]);
    }

    public function test_ai_disabled_organization_returns_403(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Disabled AI Org',
        ]);
        $organization->update([
            'settings' => array_merge(
                $organization->settings ?? [],
                ['ai_enabled' => false],
            ),
        ]);

        $this->actingAs($user)
            ->get(route('organizations.projects.onboarding', $organization))
            ->assertForbidden();
    }
}
