<?php

namespace App\Http\Controllers;

use App\Enums\DeliveryStatus;
use App\Enums\ExportJobStatus;
use App\Models\ExportJob;
use App\Models\NotificationDelivery;
use App\Models\Organization;
use App\Support\CommandCentreResourcePresenter;
use App\Support\EffectivePermissionService;
use App\Support\OrganizationMemberResolver;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationReportsController extends Controller
{
    public function index(
        Organization $organization,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): Response {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.notification-deliveries.index'), 403);

        $exportSummary = [
            'pending' => 0,
            'processing' => 0,
            'completed' => 0,
            'failed' => 0,
        ];

        ExportJob::query()
            ->where('organization_id', $organization->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->get()
            ->each(function ($row) use (&$exportSummary): void {
                $status = $row->status instanceof ExportJobStatus
                    ? $row->status->value
                    : (string) $row->status;

                if (array_key_exists($status, $exportSummary)) {
                    $exportSummary[$status] = (int) $row->total;
                }
            });

        $recentExports = ExportJob::query()
            ->where('organization_id', $organization->id)
            ->with('requestedByMember:id,display_name')
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(fn (ExportJob $job): array => [
                'id' => $job->id,
                'export_type' => $job->export_type->value,
                'status' => $job->status->value,
                'requested_by' => $job->requestedByMember?->display_name,
                'error_message' => $job->error_message,
                'completed_at' => $job->completed_at?->toIso8601String(),
                'created_at' => $job->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        $since = now()->subDays(7);

        $deliverySummary = [
            'sent_last_7_days' => NotificationDelivery::query()
                ->where('organization_id', $organization->id)
                ->where('status', DeliveryStatus::Sent)
                ->where('sent_at', '>=', $since)
                ->count(),
            'failed_last_7_days' => NotificationDelivery::query()
                ->where('organization_id', $organization->id)
                ->where('status', DeliveryStatus::Failed)
                ->where('failed_at', '>=', $since)
                ->count(),
        ];

        $recentFailedDeliveries = NotificationDelivery::query()
            ->where('organization_id', $organization->id)
            ->where('status', DeliveryStatus::Failed)
            ->orderByDesc('failed_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(fn (NotificationDelivery $delivery): array => [
                'id' => $delivery->id,
                'event_type' => $delivery->event_type,
                'recipient_email' => $delivery->recipient_email,
                'subject' => $delivery->subject,
                'error_message' => $delivery->error_message,
                'failed_at' => $delivery->failed_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        return Inertia::render('organizations/settings/reports', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'exportSummary' => $exportSummary,
            'recentExports' => $recentExports,
            'deliverySummary' => $deliverySummary,
            'recentFailedDeliveries' => $recentFailedDeliveries,
            'permissions' => CommandCentreResourcePresenter::permissions($permissions, $member),
        ]);
    }
}
