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
use App\Support\OnboardingProposalGenerator;
use App\Support\OrganizationMemberResolver;
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
        OnboardingProposalGenerator $generator,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization($request->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.ai-onboarding.propose'), 403);

        $validated = $request->validated();

        /** @var AiSession $session */
        $session = AiSession::query()->findOrFail($validated['ai_session_id']);

        abort_unless($session->organization_id === $organization->id, 404);
        abort_unless($session->organization_member_id === $member->id, 403);

        AiMessage::query()->create([
            'ai_session_id' => $session->id,
            'role' => AiMessageRole::User,
            'content' => $validated['brief'],
        ]);

        $proposal = $generator->propose(
            $session,
            $member,
            $validated['brief'],
            $validated['team'] ?? [],
        );

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

        $aiOnboardingProposal->update([
            'payload' => $validated['payload'],
            'summary' => $validated['summary'] ?? $aiOnboardingProposal->summary,
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
