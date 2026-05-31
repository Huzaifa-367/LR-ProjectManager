<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationAiEnabled
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Organization|null $organization */
        $organization = $request->route('organization');

        if ($organization === null) {
            return $next($request);
        }

        $settings = $organization->settings ?? Organization::defaultSettings();
        $aiEnabled = (bool) ($settings['ai_enabled'] ?? true);

        abort_unless($aiEnabled, 403, __('AI features are disabled for this organization.'));

        return $next($request);
    }
}
