<?php

namespace Tests\Feature\CommandCentre;

use App\Enums\AiSessionContext;
use App\Enums\AiSessionStatus;
use App\Enums\OnboardingProposalStatus;
use App\Enums\TaskKind;
use App\Models\AiMessage;
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
                ->has('members')
                ->has('contextAssessment')
                ->has('requirements')
                ->has('wizardSteps')
                ->has('conversation'));
    }

    public function test_propose_with_sparse_brief_asks_follow_up_questions_without_creating_proposal(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Sparse Org',
        ]);
        $member = $organization->members()->where('user_id', $user->id)->firstOrFail();

        $session = AiSession::query()->create([
            'organization_id' => $organization->id,
            'organization_member_id' => $member->id,
            'user_id' => $user->id,
            'context' => AiSessionContext::ProjectOnboarding,
            'status' => AiSessionStatus::Active,
        ]);

        $this->actingAs($user)
            ->post(route('organizations.ai-onboarding.propose', $organization), [
                'ai_session_id' => $session->id,
                'brief' => 'Launch TAP in three new markets by Q4',
                'team' => [[
                    'organization_member_id' => $member->id,
                    'project_role_slug' => 'project_lead',
                ]],
            ])
            ->assertRedirect(route('organizations.projects.onboarding', $organization));

        $this->assertDatabaseMissing('ai_onboarding_proposals', [
            'ai_session_id' => $session->id,
            'status' => OnboardingProposalStatus::PendingReview->value,
        ]);

        $this->assertDatabaseHas('ai_messages', [
            'ai_session_id' => $session->id,
            'role' => 'assistant',
        ]);
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
                'brief' => $this->completeOnboardingBrief(),
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

        $proposal = AiOnboardingProposal::query()
            ->where('ai_session_id', $session->id)
            ->where('status', OnboardingProposalStatus::PendingReview)
            ->firstOrFail();

        $this->assertGreaterThanOrEqual(2, count($proposal->payload['tasks'] ?? []));
        $this->assertNotEmpty($proposal->payload['project']['name'] ?? '');
    }

    public function test_propose_generates_plan_after_follow_up_answers(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Follow Up Org',
        ]);
        $member = $organization->members()->where('user_id', $user->id)->firstOrFail();

        $session = AiSession::query()->create([
            'organization_id' => $organization->id,
            'organization_member_id' => $member->id,
            'user_id' => $user->id,
            'context' => AiSessionContext::ProjectOnboarding,
            'status' => AiSessionStatus::Active,
        ]);

        $this->actingAs($user)
            ->post(route('organizations.ai-onboarding.propose', $organization), [
                'ai_session_id' => $session->id,
                'brief' => 'Launch TAP in three new markets by Q4',
                'team' => [[
                    'organization_member_id' => $member->id,
                    'project_role_slug' => 'project_lead',
                ]],
            ])
            ->assertRedirect(route('organizations.projects.onboarding', $organization));

        $this->actingAs($user)
            ->post(route('organizations.ai-onboarding.propose', $organization), [
                'ai_session_id' => $session->id,
                'answers' => [
                    'project_name' => 'TAP Expansion',
                    'objective' => 'Launch TAP in three new markets with measurable adoption targets.',
                    'work_items' => "- Market research\n- Vendor onboarding",
                    'timeline' => 'Q4 rollout with Q3 discovery',
                ],
                'team' => [[
                    'organization_member_id' => $member->id,
                    'project_role_slug' => 'project_lead',
                ]],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ai_onboarding_proposals', [
            'ai_session_id' => $session->id,
            'status' => OnboardingProposalStatus::PendingReview->value,
        ]);
    }

    public function test_propose_parses_structured_brief_into_detailed_tasks(): void
    {
        $this->fakeOnboardingPlan([
            'project_name' => 'Vendor Portal',
            'objective' => 'Replace legacy vendor onboarding with a secure self-service portal.',
            'next_action' => 'Integrate SSO with identity provider',
            'tasks' => [
                [
                    'title' => 'Integrate SSO with identity provider',
                    'description' => "Objective: Enable secure vendor login.\nSteps:\n- Configure IdP integration\n- Validate SSO flows\nAcceptance: SSO passes UAT.",
                    'priority' => 'high',
                    'deadline_date' => now()->addWeek()->toDateString(),
                ],
                [
                    'title' => 'Migrate legacy vendor records',
                    'description' => "Objective: Move vendors to the new portal.\nSteps:\n- Map legacy fields\n- Run migration pilot\nAcceptance: Pilot vendors onboarded.",
                    'priority' => 'high',
                    'deadline_date' => null,
                ],
            ],
            'decisions' => [
                ['title' => 'Approve go-live date'],
            ],
            'reminders' => [
                [
                    'title' => 'Weekly rollout sync',
                    'description' => 'Track SSO, migration, and UAT progress.',
                ],
            ],
        ]);

        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Structured Org',
        ]);
        $member = $organization->members()->where('user_id', $user->id)->firstOrFail();

        $session = AiSession::query()->create([
            'organization_id' => $organization->id,
            'organization_member_id' => $member->id,
            'user_id' => $user->id,
            'context' => AiSessionContext::ProjectOnboarding,
            'status' => AiSessionStatus::Active,
        ]);

        $brief = <<<'TXT'
Project: Vendor Portal

Objective: Replace legacy vendor onboarding with a secure self-service portal.

Scope:
- Integrate SSO with identity provider
- Migrate legacy vendor records
- UAT with pilot vendors

Timeline:
- Q2: SSO integration
- Q3: Pilot UAT and go-live decision

Decisions needed:
- Approve go-live date
TXT;

        $this->actingAs($user)
            ->post(route('organizations.ai-onboarding.propose', $organization), [
                'ai_session_id' => $session->id,
                'brief' => $brief,
                'team' => [[
                    'organization_member_id' => $member->id,
                    'project_role_slug' => 'project_lead',
                ]],
            ])
            ->assertRedirect();

        $proposal = AiOnboardingProposal::query()
            ->where('ai_session_id', $session->id)
            ->where('status', OnboardingProposalStatus::PendingReview)
            ->firstOrFail();

        $taskTitles = collect($proposal->payload['tasks'] ?? [])->pluck('title')->all();
        $taskDescriptions = collect($proposal->payload['tasks'] ?? [])->pluck('description')->all();

        $this->assertSame('Vendor Portal', $proposal->payload['project']['name'] ?? '');
        $this->assertContains('Integrate SSO with identity provider', $taskTitles);
        $this->assertContains('Migrate legacy vendor records', $taskTitles);
        $this->assertTrue(
            collect($taskDescriptions)->contains(fn (string $description): bool => str_contains($description, 'Acceptance:')),
        );
        $this->assertTrue(
            collect($proposal->payload['decisions'] ?? [])->pluck('title')->contains(
                fn (string $title): bool => str_contains(strtolower($title), 'go-live'),
            ),
        );
    }

    public function test_update_preserves_team_and_tasks_when_editing_project_name(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Update Org',
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
                'project' => [
                    'name' => 'Original Name',
                    'objective' => 'Keep objective',
                    'next_action' => 'First task',
                ],
                'team' => [[
                    'organization_member_id' => $member->id,
                    'project_role_slug' => 'project_lead',
                    'display_name' => $member->display_name,
                ]],
                'tasks' => [[
                    'title' => 'Detailed task',
                    'description' => 'Task description',
                    'priority' => 'high',
                    'status' => 'pending',
                    'assignee_member_ids' => [$member->id],
                    'kind' => 'task',
                ]],
                'decisions' => [[
                    'title' => 'Approve budget',
                    'sort_order' => 1,
                    'assignee_member_ids' => [$member->id],
                ]],
                'reminders' => [[
                    'title' => 'Weekly sync',
                    'description' => 'Progress review',
                    'assignee_member_ids' => [$member->id],
                ]],
            ],
            'version' => 1,
        ]);

        $this->actingAs($user)
            ->patch(route('organizations.ai-onboarding.update', [$organization, $proposal]), [
                'payload' => [
                    'project' => [
                        'name' => 'Renamed Project',
                        'objective' => 'Keep objective',
                        'next_action' => 'First task',
                    ],
                    'team' => json_encode([[
                        'organization_member_id' => $member->id,
                        'project_role_slug' => 'project_lead',
                        'display_name' => $member->display_name,
                    ]]),
                    'tasks' => json_encode([[
                        'title' => 'Detailed task',
                        'description' => 'Task description',
                        'priority' => 'high',
                        'status' => 'pending',
                        'assignee_member_ids' => [$member->id],
                        'kind' => 'task',
                    ]]),
                    'decisions' => json_encode([[
                        'title' => 'Approve budget',
                        'sort_order' => 1,
                        'assignee_member_ids' => [$member->id],
                    ]]),
                    'reminders' => json_encode([[
                        'title' => 'Weekly sync',
                        'description' => 'Progress review',
                        'assignee_member_ids' => [$member->id],
                    ]]),
                ],
            ])
            ->assertRedirect();

        $proposal->refresh();

        $this->assertSame('Renamed Project', $proposal->payload['project']['name']);
        $this->assertSame('Detailed task', $proposal->payload['tasks'][0]['title']);
        $this->assertSame($member->id, $proposal->payload['team'][0]['organization_member_id']);
        $this->assertSame('Approve budget', $proposal->payload['decisions'][0]['title']);
    }

    public function test_propose_handles_invalid_utf8_in_brief(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'UTF-8 Org',
        ]);
        $member = $organization->members()->where('user_id', $user->id)->firstOrFail();

        $session = AiSession::query()->create([
            'organization_id' => $organization->id,
            'organization_member_id' => $member->id,
            'user_id' => $user->id,
            'context' => AiSessionContext::ProjectOnboarding,
            'status' => AiSessionStatus::Active,
        ]);

        $brief = "Project: Safe Plan\n\nObjective: Deliver a safe rollout with validated milestones.\n\nScope:\n- Task one \xC3\x28 with bad bytes\n- Second deliverable for validation\n\nTimeline:\n- Q4 delivery";

        $this->actingAs($user)
            ->post(route('organizations.ai-onboarding.propose', $organization), [
                'ai_session_id' => $session->id,
                'brief' => $brief,
                'team' => [[
                    'organization_member_id' => $member->id,
                    'project_role_slug' => 'project_lead',
                ]],
            ])
            ->assertRedirect();

        $proposal = AiOnboardingProposal::query()
            ->where('ai_session_id', $session->id)
            ->where('status', OnboardingProposalStatus::PendingReview)
            ->firstOrFail();

        $this->assertIsString(json_encode($proposal->payload));
        $this->assertNotEmpty($proposal->payload['tasks'] ?? []);
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
                'brief' => $this->completeOnboardingBrief('Version 2 Project'),
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

    public function test_start_fresh_abandons_session_and_clears_conversation(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Reset Org',
        ]);
        $member = $organization->members()->where('user_id', $user->id)->firstOrFail();

        $session = AiSession::query()->create([
            'organization_id' => $organization->id,
            'organization_member_id' => $member->id,
            'user_id' => $user->id,
            'context' => AiSessionContext::ProjectOnboarding,
            'status' => AiSessionStatus::Active,
        ]);

        AiMessage::query()->create([
            'ai_session_id' => $session->id,
            'role' => 'user',
            'content' => 'Old brief that should disappear after reset',
        ]);

        $this->actingAs($user)
            ->post(route('organizations.projects.onboarding.reset', $organization))
            ->assertRedirect(route('organizations.projects.onboarding', $organization));

        $session->refresh();
        $this->assertSame(AiSessionStatus::Abandoned, $session->status);

        $newSession = AiSession::query()
            ->where('organization_id', $organization->id)
            ->where('organization_member_id', $member->id)
            ->where('status', AiSessionStatus::Active)
            ->latest('id')
            ->firstOrFail();

        $this->assertNotSame($session->id, $newSession->id);
        $this->assertSame(0, $newSession->messages()->count());

        $this->actingAs($user)
            ->get(route('organizations.projects.onboarding', $organization))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('organizations/projects/onboarding')
                ->where('session.id', $newSession->id)
                ->where('conversation', []));
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

    private function completeOnboardingBrief(string $projectName = 'TAP Global Expansion'): string
    {
        return <<<TXT
Project: {$projectName}

Objective: Launch in three new markets by Q4 with clear adoption targets.

Scope:
- Complete market research for target regions
- Vendor onboarding and legal review

Timeline:
- Q3: Research and vendor selection
- Q4: Rollout
TXT;
    }
}
