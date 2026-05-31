<?php

namespace App\Http\Controllers\CommandCentre;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommandCentre\ReorderFocusPinsRequest;
use App\Http\Requests\CommandCentre\StoreFocusPinRequest;
use App\Models\MemberDailyFocus;
use App\Models\Organization;
use App\Support\EffectivePermissionService;
use App\Support\OrganizationMemberResolver;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class MemberDailyFocusController extends Controller
{
    public function store(
        StoreFocusPinRequest $request,
        Organization $organization,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization($request->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.focus.store'), 403);

        $validated = $request->validated();
        $focusDate = $validated['focus_date'] ?? now()->toDateString();

        $nextSort = (int) MemberDailyFocus::query()
            ->where('organization_member_id', $member->id)
            ->whereDate('focus_date', $focusDate)
            ->max('sort_order') + 1;

        MemberDailyFocus::query()->firstOrCreate(
            [
                'organization_member_id' => $member->id,
                'task_id' => $validated['task_id'],
                'focus_date' => $focusDate,
            ],
            [
                'sort_order' => $nextSort,
                'is_auto' => false,
            ],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task pinned to focus.')]);

        return back();
    }

    public function reorder(
        ReorderFocusPinsRequest $request,
        Organization $organization,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization($request->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.focus.reorder'), 403);

        $focusDate = $request->validated('focus_date') ?? now()->toDateString();
        $orderedIds = $request->validated('ordered_ids');

        foreach ($orderedIds as $index => $focusId) {
            MemberDailyFocus::query()
                ->where('organization_member_id', $member->id)
                ->whereKey($focusId)
                ->whereDate('focus_date', $focusDate)
                ->update(['sort_order' => $index + 1]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Focus list reordered.')]);

        return back();
    }

    public function destroy(
        Organization $organization,
        MemberDailyFocus $memberDailyFocus,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.focus.destroy'), 403);
        abort_unless($memberDailyFocus->organization_member_id === $member->id, 403);

        $memberDailyFocus->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Focus pin removed.')]);

        return back();
    }
}
