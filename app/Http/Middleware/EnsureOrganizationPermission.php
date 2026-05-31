<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Models\User;
use App\Support\EffectivePermissionService;
use App\Support\OrganizationMemberResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        /** @var User $user */
        $user = $request->user();

        /** @var Organization $organization */
        $organization = $request->route('organization');

        $member = app(OrganizationMemberResolver::class)->requireForOrganization($user, $organization);

        abort_unless(
            app(EffectivePermissionService::class)->memberCan($member, $permission),
            403,
        );

        return $next($request);
    }
}
