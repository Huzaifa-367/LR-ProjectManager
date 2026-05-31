<?php

namespace App\Support;

use App\Enums\OrganizationMemberStatus;
use App\Models\OrganizationMember;
use App\Models\User;

final class OrganizationMemberLinker
{
    public function normalizeEmail(?string $email): ?string
    {
        if ($email === null || trim($email) === '') {
            return null;
        }

        return strtolower(trim($email));
    }

    public function resolveUserId(?int $userId, ?string $email): ?int
    {
        if ($userId !== null) {
            return $userId;
        }

        $normalizedEmail = $this->normalizeEmail($email);

        if ($normalizedEmail === null) {
            return null;
        }

        return User::query()
            ->where('email', $normalizedEmail)
            ->value('id');
    }

    public function linkPendingMembershipsForUser(User $user): void
    {
        $normalizedEmail = $this->normalizeEmail($user->email);

        if ($normalizedEmail === null) {
            return;
        }

        $pendingMembers = OrganizationMember::query()
            ->whereNull('user_id')
            ->whereNotNull('email')
            ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
            ->whereIn('status', [
                OrganizationMemberStatus::Active->value,
                OrganizationMemberStatus::Invited->value,
            ])
            ->get();

        foreach ($pendingMembers as $member) {
            $alreadyLinked = OrganizationMember::query()
                ->where('organization_id', $member->organization_id)
                ->where('user_id', $user->id)
                ->exists();

            if ($alreadyLinked) {
                continue;
            }

            $member->update(['user_id' => $user->id]);
        }
    }
}
