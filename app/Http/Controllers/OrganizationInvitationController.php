<?php

namespace App\Http\Controllers;

use App\Enums\InvitationStatus;
use App\Http\Requests\Organizations\StoreOrganizationInvitationRequest;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Support\EffectivePermissionService;
use App\Support\OrganizationInvitationService;
use App\Support\OrganizationMemberResolver;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class OrganizationInvitationController extends Controller
{
    public function store(
        StoreOrganizationInvitationRequest $request,
        Organization $organization,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
        OrganizationInvitationService $invitationService,
    ): RedirectResponse {
        $actor = $memberResolver->requireForOrganization($request->user(), $organization);

        abort_unless($permissions->memberCan($actor, 'org.invitations.store'), 403);

        $validated = $request->validated();

        $result = $invitationService->create(
            $organization,
            $actor,
            $validated['email'],
            (int) $validated['organization_role_id'],
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Invitation email sent to :email.', ['email' => $validated['email']]),
        ]);

        return back();
    }

    public function destroy(
        Organization $organization,
        OrganizationInvitation $organizationInvitation,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
        OrganizationInvitationService $invitationService,
    ): RedirectResponse {
        $actor = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless($organizationInvitation->organization_id === $organization->id, 404);
        abort_unless($permissions->memberCan($actor, 'org.invitations.destroy'), 403);
        abort_unless($organizationInvitation->status === InvitationStatus::Pending, 422);

        $invitationService->revoke($organizationInvitation, $actor);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation revoked.')]);

        return back();
    }

    public function resend(
        Organization $organization,
        OrganizationInvitation $organizationInvitation,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
        OrganizationInvitationService $invitationService,
    ): RedirectResponse {
        $actor = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless($organizationInvitation->organization_id === $organization->id, 404);
        abort_unless($permissions->memberCan($actor, 'org.invitations.resend'), 403);
        abort_unless($organizationInvitation->status === InvitationStatus::Pending, 422);

        $invitationService->resend($organizationInvitation, $actor);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Invitation email resent to :email.', [
                'email' => $organizationInvitation->email,
            ]),
        ]);

        return back();
    }
}
