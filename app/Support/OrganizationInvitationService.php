<?php

namespace App\Support;

use App\Enums\InvitationStatus;
use App\Enums\OrganizationMemberStatus;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class OrganizationInvitationService
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly PersonalMailLinkageService $mailLinkageService,
    ) {}

    /**
     * @return array{invitation: OrganizationInvitation, token: string, accept_url: string}
     */
    public function create(
        Organization $organization,
        OrganizationMember $invitedBy,
        string $email,
        int $organizationRoleId,
    ): array {
        $normalizedEmail = strtolower(trim($email));

        $existingPending = OrganizationInvitation::query()
            ->where('organization_id', $organization->id)
            ->where('email', $normalizedEmail)
            ->where('status', InvitationStatus::Pending)
            ->where('expires_at', '>', now())
            ->exists();

        if ($existingPending) {
            throw ValidationException::withMessages([
                'email' => __('A pending invitation already exists for this email.'),
            ]);
        }

        $token = Str::random(64);

        $invitation = OrganizationInvitation::query()->create([
            'organization_id' => $organization->id,
            'email' => $normalizedEmail,
            'organization_role_id' => $organizationRoleId,
            'invited_by_member_id' => $invitedBy->id,
            'token_hash' => hash('sha256', $token),
            'status' => InvitationStatus::Pending,
            'expires_at' => now()->addDays(7),
        ]);

        $this->activityLogger->log(
            $organization->id,
            $invitedBy->id,
            $invitation,
            'member.invited',
            ['email' => $normalizedEmail],
        );

        $acceptUrl = route('invitations.show', ['token' => $token]);

        $this->mailLinkageService->sendInvitation(
            $organization,
            $invitedBy,
            $invitation->fresh(['role', 'organization']),
            $acceptUrl,
        );

        return [
            'invitation' => $invitation->fresh(['role', 'organization']),
            'token' => $token,
            'accept_url' => $acceptUrl,
        ];
    }

    /**
     * @return array{invitation: OrganizationInvitation, accept_url: string}
     */
    public function resend(
        OrganizationInvitation $invitation,
        OrganizationMember $actor,
    ): array {
        abort_unless($invitation->status === InvitationStatus::Pending, 422);

        if ($invitation->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'invitation' => __('This invitation has expired. Create a new invitation instead.'),
            ]);
        }

        $token = Str::random(64);

        $invitation->update([
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays(7),
            'invited_by_member_id' => $actor->id,
        ]);

        $invitation->loadMissing(['organization', 'role']);
        $acceptUrl = route('invitations.show', ['token' => $token]);

        $this->mailLinkageService->sendInvitation(
            $invitation->organization,
            $actor,
            $invitation->fresh(['role', 'organization']),
            $acceptUrl,
        );

        $this->activityLogger->log(
            $invitation->organization_id,
            $actor->id,
            $invitation,
            'member.invitation_resent',
            ['email' => $invitation->email],
        );

        return [
            'invitation' => $invitation->fresh(['role', 'organization']),
            'accept_url' => $acceptUrl,
        ];
    }

    public function findPendingByToken(string $token): ?OrganizationInvitation
    {
        return OrganizationInvitation::query()
            ->where('token_hash', hash('sha256', $token))
            ->where('status', InvitationStatus::Pending)
            ->where('expires_at', '>', now())
            ->with(['organization', 'role'])
            ->first();
    }

    public function accept(OrganizationInvitation $invitation, User $user): OrganizationMember
    {
        if (strtolower($user->email) !== strtolower($invitation->email)) {
            throw ValidationException::withMessages([
                'email' => __('This invitation was sent to a different email address.'),
            ]);
        }

        if ($invitation->status !== InvitationStatus::Pending || $invitation->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'invitation' => __('This invitation is no longer valid.'),
            ]);
        }

        return DB::transaction(function () use ($invitation, $user): OrganizationMember {
            $existingMember = OrganizationMember::query()
                ->where('organization_id', $invitation->organization_id)
                ->where(function ($query) use ($user, $invitation): void {
                    $query->where('user_id', $user->id)
                        ->orWhere('email', $invitation->email);
                })
                ->first();

            if ($existingMember instanceof OrganizationMember) {
                $existingMember->update([
                    'user_id' => $user->id,
                    'organization_role_id' => $invitation->organization_role_id,
                    'display_name' => $existingMember->display_name ?: $user->name,
                    'email' => $invitation->email,
                    'status' => OrganizationMemberStatus::Active,
                    'joined_at' => $existingMember->joined_at ?? now(),
                ]);
                $member = $existingMember->fresh();
            } else {
                $isFirstOrg = ! OrganizationMember::query()
                    ->where('user_id', $user->id)
                    ->where('status', OrganizationMemberStatus::Active)
                    ->exists();

                $member = OrganizationMember::query()->create([
                    'organization_id' => $invitation->organization_id,
                    'user_id' => $user->id,
                    'organization_role_id' => $invitation->organization_role_id,
                    'display_name' => $user->name,
                    'email' => $invitation->email,
                    'status' => OrganizationMemberStatus::Active,
                    'is_primary_org' => $isFirstOrg,
                    'joined_at' => now(),
                ]);
            }

            $invitation->update([
                'status' => InvitationStatus::Accepted,
                'accepted_at' => now(),
                'organization_member_id' => $member->id,
            ]);

            $this->activityLogger->log(
                $invitation->organization_id,
                $member->id,
                $member,
                'member.joined',
                ['invitation_id' => $invitation->id],
            );

            return $member;
        });
    }

    public function revoke(
        OrganizationInvitation $invitation,
        OrganizationMember $actor,
    ): void {
        abort_unless($invitation->status === InvitationStatus::Pending, 422);

        $invitation->update(['status' => InvitationStatus::Revoked]);

        $this->activityLogger->log(
            $invitation->organization_id,
            $actor->id,
            $invitation,
            'member.invitation_revoked',
            ['email' => $invitation->email],
        );
    }
}
