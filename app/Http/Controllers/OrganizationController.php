<?php

namespace App\Http\Controllers;

use App\Http\Requests\Organizations\StoreOrganizationRequest;
use App\Http\Requests\Organizations\UpdateOrganizationRequest;
use App\Models\Organization;
use App\Support\CommandCentreResourcePresenter;
use App\Support\EffectivePermissionService;
use App\Support\OrganizationBootstrapService;
use App\Support\OrganizationMemberResolver;
use App\Support\SelectedOrganizationManager;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function index(SelectedOrganizationManager $selectedOrganizationManager): Response
    {
        $context = $selectedOrganizationManager->sharedContext(request());

        return Inertia::render('organizations/index', [
            'organizations' => $context['organizations'],
            'pendingInvitations' => $context['pendingInvitations'],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('organizations/create', [
            'defaultTimezone' => config('app.timezone', 'UTC'),
        ]);
    }

    public function store(
        StoreOrganizationRequest $request,
        OrganizationBootstrapService $bootstrapService,
    ): RedirectResponse {
        $organization = $bootstrapService->create(
            $request->user(),
            $request->validated(),
            $request,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Organization created.')]);

        return to_route('organizations.command-centre.index', $organization);
    }

    public function show(
        Organization $organization,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): Response {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.organizations.show'), 403);

        $settings = $organization->settings ?? Organization::defaultSettings();

        return Inertia::render('organizations/settings/index', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'settings' => [
                    'timezone' => $settings['timezone'] ?? config('app.timezone', 'UTC'),
                    'focus_cap' => $settings['focus_cap'] ?? 10,
                    'ai_enabled' => $settings['ai_enabled'] ?? true,
                ],
            ],
            'permissions' => CommandCentreResourcePresenter::permissions($permissions, $member),
        ]);
    }

    public function update(
        UpdateOrganizationRequest $request,
        Organization $organization,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization($request->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.organizations.update'), 403);

        $validated = $request->validated();
        $settings = $organization->settings ?? Organization::defaultSettings();

        if (isset($validated['settings'])) {
            $settings = array_merge($settings, $validated['settings']);
            unset($validated['settings']);
        }

        $organization->fill($validated);
        $organization->settings = $settings;
        $organization->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Organization updated.')]);

        return back();
    }
}
