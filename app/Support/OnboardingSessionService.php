<?php

namespace App\Support;

use App\Enums\AiSessionContext;
use App\Enums\AiSessionStatus;
use App\Enums\OnboardingProposalStatus;
use App\Models\AiOnboardingProposal;
use App\Models\AiSession;
use App\Models\Organization;
use App\Models\OrganizationMember;
use Illuminate\Support\Facades\DB;

final class OnboardingSessionService
{
    public function resolveOrCreateActive(
        Organization $organization,
        OrganizationMember $member,
        int $userId,
    ): AiSession {
        $existing = $this->findActiveSession($organization, $member);

        if ($existing !== null) {
            return $existing;
        }

        return $this->createSession($organization, $member, $userId);
    }

    public function startFresh(
        Organization $organization,
        OrganizationMember $member,
        int $userId,
    ): AiSession {
        return DB::transaction(function () use ($organization, $member, $userId): AiSession {
            $this->abandonActiveSessions($organization, $member);

            return $this->createSession($organization, $member, $userId);
        });
    }

    private function findActiveSession(
        Organization $organization,
        OrganizationMember $member,
    ): ?AiSession {
        return AiSession::query()
            ->where('organization_id', $organization->id)
            ->where('organization_member_id', $member->id)
            ->where('context', AiSessionContext::ProjectOnboarding)
            ->where('status', AiSessionStatus::Active)
            ->latest('id')
            ->first();
    }

    private function abandonActiveSessions(
        Organization $organization,
        OrganizationMember $member,
    ): void {
        $activeSessions = AiSession::query()
            ->where('organization_id', $organization->id)
            ->where('organization_member_id', $member->id)
            ->where('context', AiSessionContext::ProjectOnboarding)
            ->where('status', AiSessionStatus::Active)
            ->get();

        foreach ($activeSessions as $session) {
            AiOnboardingProposal::query()
                ->where('ai_session_id', $session->id)
                ->whereIn('status', [
                    OnboardingProposalStatus::PendingReview,
                    OnboardingProposalStatus::Approved,
                ])
                ->update(['status' => OnboardingProposalStatus::Superseded]);

            $session->update(['status' => AiSessionStatus::Abandoned]);
        }
    }

    private function createSession(
        Organization $organization,
        OrganizationMember $member,
        int $userId,
    ): AiSession {
        return AiSession::query()->create([
            'organization_id' => $organization->id,
            'organization_member_id' => $member->id,
            'user_id' => $userId,
            'context' => AiSessionContext::ProjectOnboarding,
            'status' => AiSessionStatus::Active,
        ]);
    }
}
