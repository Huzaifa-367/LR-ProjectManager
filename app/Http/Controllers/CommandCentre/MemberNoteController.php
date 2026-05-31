<?php

namespace App\Http\Controllers\CommandCentre;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommandCentre\StoreMemberNoteRequest;
use App\Http\Requests\CommandCentre\UpdateMemberNoteRequest;
use App\Models\MemberNote;
use App\Models\Organization;
use App\Support\EffectivePermissionService;
use App\Support\OrganizationMemberResolver;
use Illuminate\Http\RedirectResponse;

class MemberNoteController extends Controller
{
    public function store(
        StoreMemberNoteRequest $request,
        Organization $organization,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization($request->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.notes.store'), 403);

        $nextSort = (int) MemberNote::query()
            ->where('organization_member_id', $member->id)
            ->max('sort_order') + 1;

        MemberNote::query()->create([
            'organization_member_id' => $member->id,
            'body' => $request->validated('body'),
            'sort_order' => $nextSort,
        ]);

        return back();
    }

    public function update(
        UpdateMemberNoteRequest $request,
        Organization $organization,
        MemberNote $memberNote,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization($request->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.notes.update'), 403);
        abort_unless($memberNote->organization_member_id === $member->id, 403);

        $memberNote->update($request->validated());

        return back();
    }

    public function destroy(
        Organization $organization,
        MemberNote $memberNote,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.notes.destroy'), 403);
        abort_unless($memberNote->organization_member_id === $member->id, 403);

        $memberNote->delete();

        return back();
    }
}
