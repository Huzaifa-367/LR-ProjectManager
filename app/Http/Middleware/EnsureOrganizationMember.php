<?php

namespace App\Http\Middleware;

use App\Enums\OrganizationMemberStatus;
use App\Models\Organization;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationMember
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User $user */
        $user = $request->user();

        /** @var Organization $organization */
        $organization = $request->route('organization');

        $isActiveMember = $organization->members()
            ->where('user_id', $user->id)
            ->where('status', OrganizationMemberStatus::Active->value)
            ->exists();

        abort_unless($isActiveMember, 403);

        return $next($request);
    }
}
