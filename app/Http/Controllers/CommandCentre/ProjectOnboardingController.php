<?php

namespace App\Http\Controllers\CommandCentre;

use App\Enums\AiMessageRole;
use App\Enums\OnboardingProposalStatus;
use App\Http\Controllers\Controller;
use App\Models\AiOnboardingProposal;
use App\Models\AiSession;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Support\CommandCentreResourcePresenter;
use App\Support\EffectivePermissionService;
use App\Support\OnboardingBriefAnalyzer;
use App\Support\OnboardingConversationComposer;
use App\Support\OnboardingRequirementRegistry;
use App\Support\OnboardingSessionService;
use App\Support\OrganizationMemberResolver;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProjectOnboardingController extends Controller
{
    public function create(
        Organization $organization,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
        OnboardingConversationComposer $conversationComposer,
        OnboardingBriefAnalyzer $briefAnalyzer,
        OnboardingSessionService $onboardingSessionService,
    ): Response {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.ai-onboarding.start'), 403);

        $session = $onboardingSessionService->resolveOrCreateActive(
            $organization,
            $member,
            request()->user()->id,
        );

        $members = OrganizationMember::query()
            ->where('organization_id', $organization->id)
            ->where('status', 'active')
            ->orderBy('display_name')
            ->get(['id', 'display_name'])
            ->map(fn (OrganizationMember $row): array => [
                'id' => $row->id,
                'display_name' => $row->display_name,
            ])
            ->values()
            ->all();

        $latestProposal = AiOnboardingProposal::query()
            ->where('ai_session_id', $session->id)
            ->whereIn('status', [
                OnboardingProposalStatus::PendingReview,
                OnboardingProposalStatus::Approved,
            ])
            ->orderByDesc('version')
            ->first();

        $composedBrief = $conversationComposer->compose($session, '', []);
        $hasPriorSubmission = $session->messages()
            ->where('role', AiMessageRole::User)
            ->exists();
        $contextAssessment = $briefAnalyzer->assess($composedBrief, 0, $hasPriorSubmission);
        $pendingQuestions = $this->resolvePendingQuestions($session);

        if ($pendingQuestions !== null && ! $contextAssessment['is_complete']) {
            $contextAssessment = $pendingQuestions;
        }

        return Inertia::render('organizations/projects/onboarding', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
            ],
            'session' => CommandCentreResourcePresenter::aiSession($session),
            'members' => $members,
            'proposal' => $latestProposal
                ? CommandCentreResourcePresenter::onboardingProposal($latestProposal)
                : null,
            'conversation' => $session->messages()
                ->orderBy('id')
                ->get()
                ->map(fn ($message): array => [
                    'id' => $message->id,
                    'role' => $message->role->value,
                    'content' => $message->content,
                ])
                ->values()
                ->all(),
            'contextAssessment' => $contextAssessment,
            'requirements' => OnboardingRequirementRegistry::definitions(),
            'wizardSteps' => OnboardingRequirementRegistry::wizardSteps(),
            'initialPastePlaceholder' => OnboardingRequirementRegistry::initialPastePlaceholder(),
            'initialPasteGuide' => OnboardingRequirementRegistry::initialPasteGuide(),
            'exampleBriefs' => OnboardingRequirementRegistry::exampleBriefs(),
            'permissions' => CommandCentreResourcePresenter::permissions($permissions, $member),
        ]);
    }

    public function reset(
        Organization $organization,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
        OnboardingSessionService $onboardingSessionService,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.ai-onboarding.start'), 403);

        $onboardingSessionService->startFresh(
            $organization,
            $member,
            request()->user()->id,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Onboarding session reset. You can start a new project brief.'),
        ]);

        return to_route('organizations.projects.onboarding', $organization);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolvePendingQuestions(AiSession $session): ?array
    {
        $latestAssistant = $session->messages()
            ->where('role', AiMessageRole::Assistant)
            ->orderByDesc('id')
            ->first();

        if ($latestAssistant === null) {
            return null;
        }

        $actions = $latestAssistant->proposed_actions;

        if (! is_array($actions) || ($actions['type'] ?? null) !== 'onboarding_context_questions') {
            return null;
        }

        $assessment = $actions['assessment'] ?? null;

        return is_array($assessment) ? $assessment : null;
    }
}
