<?php

namespace App\Http\Controllers\CommandCentre;

use App\Enums\AiSessionContext;
use App\Enums\AiSessionStatus;
use App\Enums\OnboardingProposalStatus;
use App\Http\Controllers\Controller;
use App\Models\AiOnboardingProposal;
use App\Models\AiSession;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Support\CommandCentreResourcePresenter;
use App\Support\EffectivePermissionService;
use App\Support\OrganizationMemberResolver;
use Inertia\Inertia;
use Inertia\Response;

class ProjectOnboardingController extends Controller
{
    public function create(
        Organization $organization,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): Response {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.ai-onboarding.start'), 403);

        $session = $this->resolveOrCreateSession($organization, $member, request()->user()->id);

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
            'permissions' => CommandCentreResourcePresenter::permissions($permissions, $member),
        ]);
    }

    private function resolveOrCreateSession(
        Organization $organization,
        OrganizationMember $member,
        int $userId,
    ): AiSession {
        $existing = AiSession::query()
            ->where('organization_id', $organization->id)
            ->where('organization_member_id', $member->id)
            ->where('context', AiSessionContext::ProjectOnboarding)
            ->where('status', AiSessionStatus::Active)
            ->latest('id')
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return AiSession::query()->create([
            'organization_id' => $organization->id,
            'organization_member_id' => $member->id,
            'user_id' => $userId,
            'context' => AiSessionContext::ProjectOnboarding,
            'status' => AiSessionStatus::Active,
        ]);
    }
}
