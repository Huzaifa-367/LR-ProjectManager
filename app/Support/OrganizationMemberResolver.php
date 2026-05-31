<?php

namespace App\Support;

use App\Enums\OrganizationMemberStatus;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Http\Request;

final class OrganizationMemberResolver
{
    public function resolveForOrganization(User $user, Organization $organization): ?OrganizationMember
    {
        return OrganizationMember::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->where('status', OrganizationMemberStatus::Active->value)
            ->with('role.permissions')
            ->first();
    }

    public function resolveForSelectedOrganization(Request $request): ?OrganizationMember
    {
        $user = $request->user();

        if ($user === null) {
            return null;
        }

        $organization = app(SelectedOrganizationManager::class)->resolveSelectedOrganization($request);

        if ($organization === null) {
            return null;
        }

        return $this->resolveForOrganization($user, $organization);
    }

    public function requireForOrganization(User $user, Organization $organization): OrganizationMember
    {
        $member = $this->resolveForOrganization($user, $organization);

        abort_if($member === null, 403);

        return $member;
    }
}
