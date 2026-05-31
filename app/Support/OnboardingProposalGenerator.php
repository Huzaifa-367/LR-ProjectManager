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
        $projectName = $this->extractProjectName($brief) ?? __('New strategic project');

        if ($team === []) {
            $team = [[
                'organization_member_id' => $creator->id,
                'project_role_slug' => 'project_lead',
                'display_name' => $creator->display_name,
            ]];
        }

        $leadMemberId = (int) ($team[0]['organization_member_id'] ?? $creator->id);

        return [
            'project' => [
                'name' => $projectName,
                'objective' => $brief,
                'health' => 'active',
                'next_action' => __('Review generated task plan'),
                'progress_percent' => 0,
            ],
            'team' => $team,
            'tasks' => [
                [
                    'title' => __('Define scope and success criteria'),
                    'description' => $brief,
                    'priority' => 'high',
                    'status' => 'pending',
                    'deadline_type' => 'this_week',
                    'assignee_member_ids' => [$leadMemberId],
                    'kind' => 'task',
                ],
                [
                    'title' => __('Align stakeholders on timeline'),
                    'priority' => 'medium',
                    'status' => 'pending',
                    'deadline_type' => 'this_week',
                    'assignee_member_ids' => [$leadMemberId],
                    'kind' => 'task',
                ],
            ],
            'decisions' => [
                [
                    'title' => __('Approve initial plan'),
                    'sort_order' => 1,
                    'assignee_member_ids' => [$leadMemberId],
                ],
            ],
            'reminders' => [
                [
                    'title' => __('Weekly check-in'),
                    'description' => __('Review progress on :project', ['project' => $projectName]),
                    'meta' => ['icon' => '📅', 'is_urgent' => false],
                    'assignee_member_ids' => [$leadMemberId],
                ],
            ],
        ];
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

            $proposal = AiOnboardingProposal::query()->create([
                'ai_session_id' => $session->id,
                'organization_id' => $session->organization_id,
                'created_by_member_id' => $creator->id,
                'proposal_type' => OnboardingProposalType::Project,
                'status' => OnboardingProposalStatus::PendingReview,
                'payload' => $payload,
                'summary' => __('AI-generated plan for :name', ['name' => $payload['project']['name']]),
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

    private function extractProjectName(string $brief): ?string
    {
        $trimmed = trim($brief);

        if ($trimmed === '') {
            return null;
        }

        $firstLine = strtok($trimmed, "\n") ?: $trimmed;

        if (strlen($firstLine) > 80) {
            return substr($firstLine, 0, 77).'...';
        }

        return $firstLine;
    }
}
