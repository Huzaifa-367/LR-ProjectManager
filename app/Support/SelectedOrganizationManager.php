<?php

namespace App\Support;

use App\Enums\InvitationStatus;
use App\Enums\OrganizationMemberStatus;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class SelectedOrganizationManager
{
    public const string SESSION_KEY = 'selected_organization_id';

    /**
     * @return Builder<Organization>
     */
    public function accessibleOrganizationsQuery(User $user): Builder
    {
        return Organization::query()
            ->where(function (Builder $query) use ($user): void {
                $query->whereHas('members', function (Builder $members) use ($user): void {
                    $members->where('user_id', $user->id)
                        ->whereIn('status', [
                            OrganizationMemberStatus::Active->value,
                            OrganizationMemberStatus::Invited->value,
                        ]);
                });
            })
            ->orderBy('name');
    }

    /**
     * @return Builder<OrganizationMember>
     */
    public function activeMembershipsQuery(User $user): Builder
    {
        return OrganizationMember::query()
            ->where('user_id', $user->id)
            ->where('status', OrganizationMemberStatus::Active->value)
            ->with(['organization', 'role']);
    }

    /**
     * @return Collection<int, Organization>
     */
    public function accessibleOrganizations(User $user): Collection
    {
        return $this->accessibleOrganizationsQuery($user)->get();
    }

    public function setSelectedOrganizationId(Request $request, int $organizationId): void
    {
        $request->session()->put(self::SESSION_KEY, $organizationId);
    }

    public function requireSelectedOrganization(Request $request): Organization
    {
        $organization = $this->resolveSelectedOrganization($request);

        if ($organization === null) {
            throw new HttpResponseException(
                redirect()->route('organizations.index'),
            );
        }

        return $organization;
    }

    public function resolveSelectedOrganization(Request $request): ?Organization
    {
        $user = $request->user();

        if ($user === null) {
            return null;
        }

        $memberships = $this->activeMembershipsQuery($user)->get();

        if ($memberships->isEmpty()) {
            return null;
        }

        $organizationIds = $memberships->pluck('organization_id');

        $sessionId = $request->session()->get(self::SESSION_KEY);

        if ($sessionId !== null && $organizationIds->contains((int) $sessionId)) {
            return Organization::query()->find((int) $sessionId);
        }

        $primaryMembership = $memberships->firstWhere('is_primary_org', true);

        if ($primaryMembership instanceof OrganizationMember) {
            $this->setSelectedOrganizationId($request, $primaryMembership->organization_id);

            return $primaryMembership->organization;
        }

        $firstMembership = $memberships->sortBy(fn (OrganizationMember $member): string => $member->organization->name)->first();

        if ($firstMembership instanceof OrganizationMember) {
            $this->setSelectedOrganizationId($request, $firstMembership->organization_id);

            return $firstMembership->organization;
        }

        return null;
    }

    /**
     * @return array{
     *     selectedOrganization: array{id: int, name: string, slug: string}|null,
     *     organizations: list<array{
     *         id: int,
     *         name: string,
     *         slug: string,
     *         membership: string,
     *         member_status: string,
     *         is_primary_org: bool
     *     }>,
     *     pendingInvitations: list<array<string, mixed>>,
     *     permissions: array{org: list<string>, projects: array<int, list<string>>}|null,
     *     notifications: array{unreadCount: int, recent: list<array<string, mixed>>}|null,
     *     projects: list<array{id: int, name: string}>,
     *     selectedProject: array{id: int, name: string}|null
     * }
     */
    public function sharedContext(Request $request): array
    {
        $user = $request->user();

        if ($user === null) {
            return [
                'selectedOrganization' => null,
                'organizations' => [],
                'pendingInvitations' => [],
                'permissions' => null,
                'notifications' => null,
                'projects' => [],
                'selectedProject' => null,
                'aiEnabled' => true,
            ];
        }

        $memberships = OrganizationMember::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [
                OrganizationMemberStatus::Active->value,
                OrganizationMemberStatus::Invited->value,
            ])
            ->with(['organization', 'role'])
            ->get();

        $selectedOrganization = $this->resolveSelectedOrganization($request);
        $permissions = null;
        $notifications = null;
        $projects = [];
        $selectedProject = null;

        if ($selectedOrganization instanceof Organization) {
            $activeMember = app(OrganizationMemberResolver::class)
                ->resolveForOrganization($user, $selectedOrganization);

            if ($activeMember !== null) {
                $projectManager = app(SelectedProjectManager::class);
                $accessibleProjects = $projectManager->accessibleProjects(
                    $selectedOrganization,
                    $activeMember,
                );
                $selectedProjectId = $projectManager->resolveSelectedProjectId(
                    $request,
                    $selectedOrganization,
                    $activeMember,
                );

                $projects = $accessibleProjects
                    ->map(fn ($project): array => [
                        'id' => $project->id,
                        'name' => $project->name,
                    ])
                    ->values()
                    ->all();

                if ($selectedProjectId !== null) {
                    $project = $accessibleProjects->firstWhere('id', $selectedProjectId);

                    if ($project !== null) {
                        $selectedProject = [
                            'id' => $project->id,
                            'name' => $project->name,
                        ];
                    }
                }

                $orgPermissions = app(EffectivePermissionService::class)
                    ->orgPermissionsForMember($activeMember);

                $permissions = [
                    'org' => $orgPermissions,
                    'projects' => [],
                ];

                if (in_array('org.notifications.index', $orgPermissions, true)) {
                    $recent = $user->notifications()
                        ->where('data->organization_id', $selectedOrganization->id)
                        ->orderByDesc('created_at')
                        ->limit(8)
                        ->get()
                        ->map(fn ($notification): array => [
                            'id' => $notification->id,
                            'title' => (string) ($notification->data['title'] ?? ''),
                            'body' => (string) ($notification->data['body'] ?? ''),
                            'action_url' => $notification->data['action_url'] ?? null,
                            'read_at' => $notification->read_at?->toIso8601String(),
                            'created_at' => $notification->created_at->toIso8601String(),
                        ])
                        ->values()
                        ->all();

                    $notifications = [
                        'unreadCount' => $user->unreadNotifications()
                            ->where('data->organization_id', $selectedOrganization->id)
                            ->count(),
                        'recent' => $recent,
                    ];
                }
            }
        }

        return [
            'selectedOrganization' => $selectedOrganization ? [
                'id' => $selectedOrganization->id,
                'name' => $selectedOrganization->name,
                'slug' => $selectedOrganization->slug,
            ] : null,
            'aiEnabled' => $selectedOrganization instanceof Organization
                ? (bool) (($selectedOrganization->settings ?? Organization::defaultSettings())['ai_enabled'] ?? true)
                : true,
            'organizations' => $memberships
                ->map(function (OrganizationMember $member) use ($user): array {
                    $membership = 'member';

                    if ($member->organization->owner_user_id === $user->id || $member->role?->slug === 'owner') {
                        $membership = 'owner';
                    } elseif ($member->status === OrganizationMemberStatus::Invited) {
                        $membership = 'invited';
                    }

                    return [
                        'id' => $member->organization_id,
                        'name' => $member->organization->name,
                        'slug' => $member->organization->slug,
                        'membership' => $membership,
                        'member_status' => $member->status->value,
                        'is_primary_org' => $member->is_primary_org,
                    ];
                })
                ->unique('id')
                ->values()
                ->all(),
            'pendingInvitations' => OrganizationInvitation::query()
                ->where('email', strtolower($user->email))
                ->where('status', InvitationStatus::Pending)
                ->where('expires_at', '>', now())
                ->with(['organization', 'role'])
                ->orderBy('expires_at')
                ->get()
                ->map(fn (OrganizationInvitation $invitation): array => [
                    'id' => $invitation->id,
                    'organization_name' => $invitation->organization->name,
                    'role_name' => $invitation->role->name,
                    'expires_at' => $invitation->expires_at->toIso8601String(),
                    'accept_url' => route('invitations.accept-pending', $invitation),
                ])
                ->values()
                ->all(),
            'permissions' => $permissions,
            'notifications' => $notifications,
            'projects' => $projects,
            'selectedProject' => $selectedProject,
        ];
    }
}
