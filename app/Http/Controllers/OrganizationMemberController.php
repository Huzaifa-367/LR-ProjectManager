<?php

namespace App\Http\Controllers;

use App\Enums\InvitationStatus;
use App\Enums\OrganizationMemberStatus;
use App\Http\Requests\Organizations\StoreOrganizationMemberRequest;
use App\Http\Requests\Organizations\UpdateOrganizationMemberRequest;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationMember;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\CommandCentreResourcePresenter;
use App\Support\EffectivePermissionService;
use App\Support\MemberNotificationPreferenceSeeder;
use App\Support\OrganizationMemberLinker;
use App\Support\OrganizationMemberResolver;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationMemberController extends Controller
{
    public function index(
        Organization $organization,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): Response {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.members.index'), 403);

        $members = OrganizationMember::query()
            ->where('organization_id', $organization->id)
            ->with('role')
            ->orderBy('display_name')
            ->get()
            ->map(fn (OrganizationMember $row): array => $this->presentMember($row))
            ->values()
            ->all();

        $roles = $organization->roles()
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug'])
            ->all();

        $invitations = [];

        if ($permissions->memberCan($member, 'org.invitations.index')) {
            $invitations = OrganizationInvitation::query()
                ->where('organization_id', $organization->id)
                ->where('status', InvitationStatus::Pending)
                ->with('role')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (OrganizationInvitation $invitation): array => [
                    'id' => $invitation->id,
                    'email' => $invitation->email,
                    'role_name' => $invitation->role->name,
                    'expires_at' => $invitation->expires_at->toIso8601String(),
                ])
                ->values()
                ->all();
        }

        return Inertia::render('organizations/settings/members', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'members' => $members,
            'invitations' => $invitations,
            'roles' => $roles,
            'permissions' => CommandCentreResourcePresenter::permissions($permissions, $member),
        ]);
    }

    public function store(
        StoreOrganizationMemberRequest $request,
        Organization $organization,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
        MemberNotificationPreferenceSeeder $notificationPreferenceSeeder,
    ): RedirectResponse {
        $actor = $memberResolver->requireForOrganization($request->user(), $organization);

        abort_unless($permissions->memberCan($actor, 'org.members.store'), 403);

        $validated = $request->validated();
        $linker = app(OrganizationMemberLinker::class);
        $email = $linker->normalizeEmail($validated['email'] ?? null);
        $userId = $linker->resolveUserId($validated['user_id'] ?? null, $email);

        if ($userId !== null) {
            $linkedUser = User::query()->findOrFail($userId);
            $validated['display_name'] = $validated['display_name'] ?: $linkedUser->name;
            $email = $email ?? $linker->normalizeEmail($linkedUser->email);
        }

        $createdMember = OrganizationMember::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $userId,
            'organization_role_id' => $validated['organization_role_id'],
            'display_name' => $validated['display_name'],
            'email' => $email,
            'title' => $validated['title'] ?? null,
            'status' => OrganizationMemberStatus::Active,
            'joined_at' => now(),
        ]);

        app(ActivityLogger::class)->log(
            $organization->id,
            $actor->id,
            $createdMember,
            'member.created',
            ['display_name' => $createdMember->display_name],
        );

        $notificationPreferenceSeeder->seedForMember($createdMember);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member added.')]);

        return back();
    }

    public function show(
        Organization $organization,
        OrganizationMember $organizationMember,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): Response {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.members.show'), 403);
        abort_unless($organizationMember->organization_id === $organization->id, 404);

        return Inertia::render('organizations/settings/members', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'members' => [$this->presentMember($organizationMember->load('role'))],
            'invitations' => [],
            'roles' => $organization->roles()->orderBy('sort_order')->get(['id', 'name', 'slug'])->all(),
            'permissions' => CommandCentreResourcePresenter::permissions($permissions, $member),
        ]);
    }

    public function update(
        UpdateOrganizationMemberRequest $request,
        Organization $organization,
        OrganizationMember $organizationMember,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $actor = $memberResolver->requireForOrganization($request->user(), $organization);

        abort_unless($permissions->memberCan($actor, 'org.members.update'), 403);
        abort_unless($organizationMember->organization_id === $organization->id, 404);

        $organizationMember->update($request->validated());

        app(ActivityLogger::class)->logForAuthenticatedUser(
            $organizationMember,
            'member.updated',
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member updated.')]);

        return back();
    }

    public function disable(
        Organization $organization,
        OrganizationMember $organizationMember,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $actor = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless($permissions->memberCan($actor, 'org.members.disable'), 403);
        abort_unless($organizationMember->organization_id === $organization->id, 404);
        abort_if($organizationMember->id === $actor->id, 403, __('You cannot disable your own membership.'));

        $organizationMember->update([
            'status' => OrganizationMemberStatus::Disabled,
        ]);

        app(ActivityLogger::class)->log(
            $organization->id,
            $actor->id,
            $organizationMember,
            'member.disabled',
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member disabled.')]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function presentMember(OrganizationMember $member): array
    {
        return [
            'id' => $member->id,
            'display_name' => $member->display_name,
            'email' => $member->email,
            'title' => $member->title,
            'status' => $member->status->value,
            'user_id' => $member->user_id,
            'role' => [
                'id' => $member->role?->id,
                'name' => $member->role?->name,
                'slug' => $member->role?->slug,
            ],
            'joined_at' => $member->joined_at?->toIso8601String(),
        ];
    }
}
