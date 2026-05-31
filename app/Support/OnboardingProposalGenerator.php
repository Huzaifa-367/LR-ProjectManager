<?php

namespace App\Support;

use App\Enums\AiMessageRole;
use App\Enums\OnboardingProposalStatus;
use App\Enums\OnboardingProposalType;
use App\Models\AiMessage;
use App\Models\AiOnboardingProposal;
use App\Models\AiSession;
use App\Models\Organization;
use App\Models\OrganizationMember;
use Illuminate\Support\Facades\DB;

final class OnboardingProposalGenerator
{
    public function __construct(
        private readonly OnboardingPlanGenerator $planGenerator,
    ) {}

    /**
     * @param  list<array{organization_member_id: int, project_role_slug: string, display_name?: string}>  $team
     * @return array{
     *     project: array<string, mixed>,
     *     team: list<array<string, mixed>>,
     *     tasks: list<array<string, mixed>>,
     *     decisions: list<array<string, mixed>>,
     *     reminders: list<array<string, mixed>>
     * }
     */
    public function buildPayload(
        Organization $organization,
        OrganizationMember $creator,
        string $brief,
        array $team = [],
    ): array {
        $team = $this->normalizeTeam($creator, $team);
        $leadMemberId = (int) ($team[0]['organization_member_id'] ?? $creator->id);
        $sanitizedBrief = Utf8::sanitize($brief);

        $structure = app(OnboardingBriefParser::class)->extractStructure($sanitizedBrief);
        $profile = app(OnboardingProjectProfileDetector::class)->detect($sanitizedBrief, $structure);

        $plan = $this->planGenerator->generate($sanitizedBrief, $leadMemberId, $profile['key']);

        return Utf8::sanitizeRecursive([
            'project' => [
                'name' => $plan['project_name'],
                'objective' => $plan['objective'],
                'health' => 'active',
                'next_action' => $plan['next_action'],
                'progress_percent' => 0,
            ],
            'team' => $team,
            'tasks' => $plan['tasks'],
            'decisions' => $plan['decisions'],
            'reminders' => $plan['reminders'],
        ]);
    }

    public function propose(
        AiSession $session,
        OrganizationMember $creator,
        string $brief,
        array $team = [],
    ): AiOnboardingProposal {
        return DB::transaction(function () use ($session, $creator, $brief, $team): AiOnboardingProposal {
            AiOnboardingProposal::query()
                ->where('ai_session_id', $session->id)
                ->where('status', OnboardingProposalStatus::PendingReview)
                ->update(['status' => OnboardingProposalStatus::Superseded]);

            $nextVersion = (int) AiOnboardingProposal::query()
                ->where('ai_session_id', $session->id)
                ->max('version') + 1;

            $payload = $this->buildPayload($session->organization, $creator, $brief, $team);

            $taskCount = count($payload['tasks']);
            $decisionCount = count($payload['decisions']);

            $proposal = AiOnboardingProposal::query()->create([
                'ai_session_id' => $session->id,
                'organization_id' => $session->organization_id,
                'created_by_member_id' => $creator->id,
                'proposal_type' => OnboardingProposalType::Project,
                'status' => OnboardingProposalStatus::PendingReview,
                'payload' => $payload,
                'summary' => Utf8::sanitize(__(
                    'Plan for :name — :tasks tasks, :decisions decisions',
                    [
                        'name' => $payload['project']['name'],
                        'tasks' => $taskCount,
                        'decisions' => $decisionCount,
                    ],
                )),
                'version' => $nextVersion,
            ]);

            AiMessage::query()->create([
                'ai_session_id' => $session->id,
                'role' => AiMessageRole::Assistant,
                'content' => $proposal->summary,
                'onboarding_proposal_id' => $proposal->id,
            ]);

            $session->update(['last_message_at' => now()]);

            return $proposal->fresh();
        });
    }

    /**
     * @param  list<array{organization_member_id: int, project_role_slug: string, display_name?: string}>  $team
     * @return list<array{organization_member_id: int, project_role_slug: string, display_name?: string}>
     */
    private function normalizeTeam(OrganizationMember $creator, array $team): array
    {
        if ($team === []) {
            return [[
                'organization_member_id' => $creator->id,
                'project_role_slug' => 'project_lead',
                'display_name' => Utf8::sanitize((string) $creator->display_name),
            ]];
        }

        $memberIds = array_map(
            fn (array $row): int => (int) $row['organization_member_id'],
            $team,
        );

        if (! in_array($creator->id, $memberIds, true)) {
            $team[] = [
                'organization_member_id' => $creator->id,
                'project_role_slug' => 'project_lead',
                'display_name' => Utf8::sanitize((string) $creator->display_name),
            ];
        }

        return array_map(function (array $row): array {
            if (isset($row['display_name']) && is_string($row['display_name'])) {
                $row['display_name'] = Utf8::sanitize($row['display_name']);
            }

            return $row;
        }, $team);
    }
}
