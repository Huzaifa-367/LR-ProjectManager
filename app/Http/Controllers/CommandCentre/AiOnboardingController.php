<?php

namespace App\Http\Controllers\CommandCentre;

use App\Enums\AiMessageRole;
use App\Enums\OnboardingProposalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CommandCentre\ProposeOnboardingRequest;
use App\Http\Requests\CommandCentre\RejectOnboardingProposalRequest;
use App\Http\Requests\CommandCentre\UpdateOnboardingProposalRequest;
use App\Models\AiMessage;
use App\Models\AiOnboardingProposal;
use App\Models\AiSession;
use App\Models\Organization;
use App\Models\Task;
use App\Support\ApplyOnboardingProposal;
use App\Support\CommandCentreResourcePresenter;
use App\Support\EffectivePermissionService;
use App\Support\OnboardingBriefAnalyzer;
use App\Support\OnboardingConversationComposer;
use App\Support\OnboardingPlanGenerationException;
use App\Support\OnboardingProposalGenerator;
use App\Support\OnboardingRateLimiter;
use App\Support\OrganizationMemberResolver;
use App\Support\Utf8;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AiOnboardingController extends Controller
{
    public function propose(
        ProposeOnboardingRequest $request,
        Organization $organization,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
        OnboardingConversationComposer $conversationComposer,
        OnboardingBriefAnalyzer $briefAnalyzer,
        OnboardingProposalGenerator $generator,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization($request->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.ai-onboarding.propose'), 403);

        $validated = $request->validated();

        /** @var AiSession $session */
        $session = AiSession::query()->findOrFail($validated['ai_session_id']);

        abort_unless($session->organization_id === $organization->id, 404);
        abort_unless($session->organization_member_id === $member->id, 403);

        $brief = Utf8::sanitize((string) ($validated['brief'] ?? ''));
        /** @var array<string, string> $answers */
        $answers = collect($validated['answers'] ?? [])
            ->map(fn ($value): string => Utf8::sanitize((string) $value))
            ->filter(fn (string $value): bool => trim($value) !== '')
            ->all();
        $team = $validated['team'] ?? [];

        $hadPriorUserMessages = $session->messages()
            ->where('role', AiMessageRole::User)
            ->exists();

        if ($brief !== '') {
            AiMessage::query()->create([
                'ai_session_id' => $session->id,
                'role' => AiMessageRole::User,
                'content' => $brief,
            ]);
        }

        if ($answers !== []) {
            AiMessage::query()->create([
                'ai_session_id' => $session->id,
                'role' => AiMessageRole::User,
                'content' => $conversationComposer->formatAnswers($answers),
            ]);
        }

        $composedBrief = $conversationComposer->compose($session, '', []);
        $hasPriorSubmission = $hadPriorUserMessages || $answers !== [];
        $assessment = $briefAnalyzer->assess($composedBrief, count($team), $hasPriorSubmission);

        if (! $assessment['is_complete']) {
            $profile = $assessment['project_profile'];
            $questionCount = count($assessment['questions']);

            AiMessage::query()->create([
                'ai_session_id' => $session->id,
                'role' => AiMessageRole::Assistant,
                'content' => trim(implode("\n\n", array_filter([
                    __('Detected project type: :type — :summary', [
                        'type' => $profile['label'],
                        'summary' => $profile['summary'],
                    ]),
                    __('I need a bit more context before generating the plan.'),
                    $questionCount > 0
                        ? __('Please answer the :count follow-up question(s) below.', ['count' => $questionCount])
                        : null,
                ]))),
                'proposed_actions' => [
                    'type' => 'onboarding_context_questions',
                    'assessment' => $assessment,
                ],
            ]);

            $session->update(['last_message_at' => now()]);

            Inertia::flash('toast', [
                'type' => 'info',
                'message' => __('Answer the follow-up questions to continue.'),
            ]);

            return to_route('organizations.projects.onboarding', $organization);
        }

        try {
            OnboardingRateLimiter::attemptPropose($organization);

            $proposal = $generator->propose(
                $session,
                $member,
                $composedBrief,
                $team,
            );
        } catch (OnboardingPlanGenerationException $exception) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $exception->getMessage(),
            ]);

            return to_route('organizations.projects.onboarding', $organization);
        } catch (\Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException $exception) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $exception->getMessage() ?: __('Too many requests. Please wait a moment and try again.'),
            ]);

            return to_route('organizations.projects.onboarding', $organization);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Project plan generated. Review before applying.'),
        ]);

        return to_route('organizations.ai-onboarding.show', [$organization, $proposal]);
    }

    public function show(
        Organization $organization,
        AiOnboardingProposal $aiOnboardingProposal,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): Response {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless($aiOnboardingProposal->organization_id === $organization->id, 404);
        abort_unless($permissions->memberCan($member, 'org.ai-onboarding.show'), 403);

        return Inertia::render('organizations/projects/onboarding/review', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
            ],
            'proposal' => CommandCentreResourcePresenter::onboardingProposal($aiOnboardingProposal),
            'permissions' => CommandCentreResourcePresenter::permissions($permissions, $member),
        ]);
    }

    public function update(
        UpdateOnboardingProposalRequest $request,
        Organization $organization,
        AiOnboardingProposal $aiOnboardingProposal,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization($request->user(), $organization);

        abort_unless($aiOnboardingProposal->organization_id === $organization->id, 404);
        abort_unless($permissions->memberCan($member, 'org.ai-onboarding.update'), 403);
        abort_unless(
            in_array($aiOnboardingProposal->status, [
                OnboardingProposalStatus::PendingReview,
                OnboardingProposalStatus::Approved,
            ], true),
            422,
        );

        $validated = $request->validated();
        $existingPayload = $aiOnboardingProposal->payload ?? [];
        $incomingPayload = $validated['payload'];

        $mergedPayload = $existingPayload;
        $mergedPayload['project'] = array_merge(
            $existingPayload['project'] ?? [],
            $incomingPayload['project'] ?? [],
        );

        foreach (['team', 'tasks', 'decisions', 'reminders'] as $key) {
            if (! array_key_exists($key, $incomingPayload)) {
                continue;
            }

            $value = $incomingPayload[$key];

            if (is_array($value) && $value !== []) {
                $mergedPayload[$key] = $value;
            }
        }

        $aiOnboardingProposal->update([
            'payload' => Utf8::sanitizeRecursive($mergedPayload),
            'summary' => isset($validated['summary'])
                ? Utf8::sanitize($validated['summary'])
                : $aiOnboardingProposal->summary,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Proposal updated.')]);

        return back();
    }

    public function approve(
        Organization $organization,
        AiOnboardingProposal $aiOnboardingProposal,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless($aiOnboardingProposal->organization_id === $organization->id, 404);
        abort_unless($permissions->memberCan($member, 'org.ai-onboarding.approve'), 403);
        abort_unless(
            $aiOnboardingProposal->status === OnboardingProposalStatus::PendingReview,
            422,
        );

        $aiOnboardingProposal->update(['status' => OnboardingProposalStatus::Approved]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Proposal approved.')]);

        return back();
    }

    public function reject(
        RejectOnboardingProposalRequest $request,
        Organization $organization,
        AiOnboardingProposal $aiOnboardingProposal,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization($request->user(), $organization);

        abort_unless($aiOnboardingProposal->organization_id === $organization->id, 404);
        abort_unless($permissions->memberCan($member, 'org.ai-onboarding.reject'), 403);
        abort_unless(
            in_array($aiOnboardingProposal->status, [
                OnboardingProposalStatus::PendingReview,
                OnboardingProposalStatus::Approved,
            ], true),
            422,
        );

        $aiOnboardingProposal->update([
            'status' => OnboardingProposalStatus::Rejected,
            'rejection_reason' => $request->validated('rejection_reason'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Proposal rejected.')]);

        return to_route('organizations.projects.onboarding', $organization);
    }

    public function apply(
        Organization $organization,
        AiOnboardingProposal $aiOnboardingProposal,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
        ApplyOnboardingProposal $applyOnboardingProposal,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless($aiOnboardingProposal->organization_id === $organization->id, 404);
        abort_unless($permissions->memberCan($member, 'org.ai-onboarding.apply'), 403);

        try {
            OnboardingRateLimiter::attemptApply($organization);
        } catch (\Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException $exception) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $exception->getMessage() ?: __('Too many requests. Please wait a moment and try again.'),
            ]);

            return back();
        }

        $taskCountBefore = Task::query()->where('organization_id', $organization->id)->count();

        $project = $applyOnboardingProposal->apply($aiOnboardingProposal, $member);

        $taskCountAfter = Task::query()->where('organization_id', $organization->id)->count();

        abort_unless($taskCountAfter > $taskCountBefore, 500);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Project created from AI proposal.'),
        ]);

        return to_route('organizations.projects.show', [$organization, $project]);
    }
}
