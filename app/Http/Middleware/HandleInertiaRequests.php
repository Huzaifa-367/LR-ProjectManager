<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\SelectedOrganizationManager;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
                'roles' => $user?->getRoleNames()->all() ?? [],
                'permissions' => $user?->getAllPermissions()->pluck('name')->all() ?? [],
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'organizationContext' => fn (): array => app(SelectedOrganizationManager::class)->sharedContext($request),
        ];
    }
}
