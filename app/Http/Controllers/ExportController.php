<?php

namespace App\Http\Controllers;

use App\Enums\ExportJobStatus;
use App\Http\Requests\Organizations\StoreExportRequest;
use App\Jobs\ProcessExportJob;
use App\Models\ExportJob;
use App\Models\Organization;
use App\Support\EffectivePermissionService;
use App\Support\OrganizationMemberResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function store(
        StoreExportRequest $request,
        Organization $organization,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization($request->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.exports.store'), 403);

        $validated = $request->validated();
        $filters = $validated['filters'] ?? [];

        if (isset($filters['project_id'])) {
            abort_unless(
                $organization->projects()->whereKey($filters['project_id'])->exists(),
                422,
            );
        }

        $exportJob = ExportJob::query()->create([
            'organization_id' => $organization->id,
            'requested_by_member_id' => $member->id,
            'export_type' => $validated['export_type'],
            'filters' => $filters !== [] ? $filters : null,
            'status' => ExportJobStatus::Pending,
            'disk' => config('filesystems.default', 'local'),
            'expires_at' => now()->addDay(),
        ]);

        ProcessExportJob::dispatch($exportJob->id);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Export started. Download will be ready shortly.'),
        ]);

        return redirect()->route('organizations.exports.show', [$organization, $exportJob]);
    }

    public function show(
        Organization $organization,
        ExportJob $exportJob,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): StreamedResponse|RedirectResponse {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless($exportJob->organization_id === $organization->id, 404);
        abort_unless($permissions->memberCan($member, 'org.exports.show'), 403);
        abort_unless($exportJob->requested_by_member_id === $member->id, 403);

        if ($exportJob->status === ExportJobStatus::Failed) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $exportJob->error_message ?? __('Export failed.'),
            ]);

            return redirect()->route('organizations.tasks.index', $organization);
        }

        if (! $exportJob->isDownloadable()) {
            Inertia::flash('toast', [
                'type' => 'info',
                'message' => __('Export is still processing. Refresh in a moment.'),
            ]);

            return redirect()->route('organizations.tasks.index', $organization);
        }

        $filename = sprintf(
            '%s-tasks-%s.csv',
            $organization->slug,
            $exportJob->completed_at?->format('Y-m-d-His') ?? $exportJob->id,
        );

        return Storage::disk($exportJob->disk)->download(
            (string) $exportJob->path,
            $filename,
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }
}
