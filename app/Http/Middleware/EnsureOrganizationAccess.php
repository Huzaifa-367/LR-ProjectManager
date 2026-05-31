<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Models\User;
use App\Support\SelectedOrganizationManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User $user */
        $user = $request->user();

        /** @var Organization $organization */
        $organization = $request->route('organization');

        $canAccess = app(SelectedOrganizationManager::class)
            ->accessibleOrganizationsQuery($user)
            ->whereKey($organization->id)
            ->exists();

        abort_unless($canAccess, 403);

        return $next($request);
    }
}
