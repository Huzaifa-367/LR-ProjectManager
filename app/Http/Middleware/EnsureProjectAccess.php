<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProjectAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Organization $organization */
        $organization = $request->route('organization');

        /** @var Project $project */
        $project = $request->route('project');

        abort_unless($project->organization_id === $organization->id, 404);

        /** @var User $user */
        $user = $request->user();

        $isMember = $organization->members()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();

        abort_unless($isMember, 403);

        return $next($request);
    }
}
