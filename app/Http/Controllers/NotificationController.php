<?php

namespace App\Http\Controllers;

use App\Http\Requests\Organizations\MarkNotificationsReadRequest;
use App\Models\Organization;
use App\Support\CommandCentreResourcePresenter;
use App\Support\EffectivePermissionService;
use App\Support\OrganizationMemberResolver;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(
        Organization $organization,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): Response {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.notifications.index'), 403);

        $user = request()->user();

        $notifications = $user->notifications()
            ->where('data->organization_id', $organization->id)
            ->orderByDesc('created_at')
            ->limit(50)
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

        return Inertia::render('organizations/notifications/index', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'notifications' => $notifications,
            'permissions' => CommandCentreResourcePresenter::permissions($permissions, $member),
        ]);
    }

    public function markRead(
        MarkNotificationsReadRequest $request,
        Organization $organization,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization($request->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.notifications.mark-read'), 403);

        $notificationIds = $request->validated('notification_ids');

        $request->user()->notifications()
            ->whereIn('id', $notificationIds)
            ->where('data->organization_id', $organization->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back();
    }
}
