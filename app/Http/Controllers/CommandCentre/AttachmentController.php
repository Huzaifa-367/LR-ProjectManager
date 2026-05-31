<?php

namespace App\Http\Controllers\CommandCentre;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommandCentre\StoreAttachmentRequest;
use App\Models\Attachment;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Task;
use App\Support\ActivityLogger;
use App\Support\EffectivePermissionService;
use App\Support\OrganizationMemberResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AttachmentController extends Controller
{
    public function store(
        StoreAttachmentRequest $request,
        Organization $organization,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization($request->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.attachments.store'), 403);

        $validated = $request->validated();
        $attachable = $this->resolveAttachable(
            $organization,
            $validated['attachable_type'],
            (int) $validated['attachable_id'],
            $member,
            $permissions,
        );

        $file = $request->file('file');
        abort_if($file === null, 422);

        $disk = config('filesystems.default', 'local');
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid()->toString().($extension !== '' ? ".{$extension}" : '');
        $path = $file->storeAs(
            "command-centre/{$organization->id}/attachments",
            $filename,
            $disk,
        );

        $attachment = Attachment::query()->create([
            'organization_id' => $organization->id,
            'attachable_type' => $attachable->getMorphClass(),
            'attachable_id' => $attachable->getKey(),
            'uploaded_by_member_id' => $member->id,
            'disk' => $disk,
            'path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => (string) ($file->getMimeType() ?? 'application/octet-stream'),
            'size_bytes' => (int) $file->getSize(),
        ]);

        app(ActivityLogger::class)->log(
            $organization->id,
            $member->id,
            $attachment,
            'attachment.uploaded',
            [
                'filename' => $attachment->original_filename,
                'attachable_type' => class_basename($attachable),
                'attachable_id' => $attachable->getKey(),
            ],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Attachment uploaded.')]);

        return back();
    }

    public function destroy(
        Organization $organization,
        Attachment $attachment,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless($attachment->organization_id === $organization->id, 404);
        abort_unless($permissions->memberCan($member, 'org.attachments.destroy'), 403);

        if ($attachment->attachable_type === Task::class) {
            $task = Task::query()->find($attachment->attachable_id);
            abort_unless($task !== null && $permissions->memberCanViewTask($member, $task), 403);
            $canDelete = $attachment->uploaded_by_member_id === $member->id
                || $permissions->memberCanUpdateTask($member, $task);
        } else {
            $canDelete = $attachment->uploaded_by_member_id === $member->id;
        }

        abort_unless($canDelete, 403);

        $attachment->deleteStoredFile();
        $attachment->delete();

        app(ActivityLogger::class)->logForAuthenticatedUser(
            $attachment,
            'attachment.deleted',
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Attachment deleted.')]);

        return back();
    }

    private function resolveAttachable(
        Organization $organization,
        string $attachableType,
        int $attachableId,
        OrganizationMember $member,
        EffectivePermissionService $permissions,
    ): Task {
        abort_unless($attachableType === 'task', 422);

        $task = Task::query()->findOrFail($attachableId);
        abort_unless($task->organization_id === $organization->id, 404);
        abort_unless($permissions->memberCanViewTask($member, $task), 403);

        return $task;
    }
}
