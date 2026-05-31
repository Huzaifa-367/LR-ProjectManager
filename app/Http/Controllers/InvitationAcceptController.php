<?php

namespace App\Http\Controllers;

use App\Enums\InvitationStatus;
use App\Models\OrganizationInvitation;
use App\Support\OrganizationInvitationService;
use App\Support\SelectedOrganizationManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvitationAcceptController extends Controller
{
    public function show(string $token, OrganizationInvitationService $invitationService): Response|RedirectResponse
    {
        $invitation = $invitationService->findPendingByToken($token);

        if ($invitation === null) {
            return redirect()
                ->route('organizations.index')
                ->with('error', __('This invitation is invalid or has expired.'));
        }

        return Inertia::render('invitations/show', [
            'token' => $token,
            'invitation' => [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'organization_name' => $invitation->organization->name,
                'role_name' => $invitation->role->name,
                'expires_at' => $invitation->expires_at->toIso8601String(),
            ],
            'canAccept' => auth()->check()
                && strtolower((string) auth()->user()?->email) === strtolower($invitation->email),
        ]);
    }

    public function accept(
        string $token,
        Request $request,
        OrganizationInvitationService $invitationService,
        SelectedOrganizationManager $organizationManager,
    ): RedirectResponse {
        $invitation = $invitationService->findPendingByToken($token);

        abort_if($invitation === null, 404);
        abort_if($request->user() === null, 403);

        $member = $invitationService->accept($invitation, $request->user());

        $organizationManager->setSelectedOrganizationId($request, $invitation->organization_id);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('You have joined :organization.', [
                'organization' => $invitation->organization->name,
            ]),
        ]);

        return redirect()->route('organizations.command-centre.index', $invitation->organization_id);
    }

    public function acceptPending(
        OrganizationInvitation $organizationInvitation,
        Request $request,
        OrganizationInvitationService $invitationService,
        SelectedOrganizationManager $organizationManager,
    ): RedirectResponse {
        abort_if($request->user() === null, 403);
        abort_unless($organizationInvitation->status === InvitationStatus::Pending, 404);
        abort_unless(
            strtolower((string) $request->user()->email) === strtolower($organizationInvitation->email),
            403,
        );

        $organizationInvitation->load('organization');
        $member = $invitationService->accept($organizationInvitation, $request->user());

        $organizationManager->setSelectedOrganizationId($request, $organizationInvitation->organization_id);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('You have joined :organization.', [
                'organization' => $organizationInvitation->organization->name,
            ]),
        ]);

        return redirect()->route('organizations.command-centre.index', $organizationInvitation->organization_id);
    }
}
