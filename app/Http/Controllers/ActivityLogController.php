<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Organization;
use App\Support\CommandCentreResourcePresenter;
use App\Support\EffectivePermissionService;
use App\Support\OrganizationMemberResolver;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function index(
        Organization $organization,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): Response {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.activity-logs.index'), 403);

        $logs = ActivityLog::query()
            ->where('organization_id', $organization->id)
            ->with('actorMember')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn (ActivityLog $log): array => [
                'id' => $log->id,
                'event' => $log->event,
                'subject_type' => class_basename($log->subject_type),
                'subject_id' => $log->subject_id,
                'properties' => $log->properties,
                'actor_name' => $log->actorMember?->display_name,
                'created_at' => $log->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        return Inertia::render('organizations/settings/activity-logs', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'logs' => $logs,
            'permissions' => CommandCentreResourcePresenter::permissions($permissions, $member),
        ]);
    }
}
