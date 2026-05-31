<?php

namespace App\Http\Controllers;

use App\Http\Requests\Organizations\UpdateMemberMailLinkageRequest;
use App\Models\MemberMailLinkage;
use App\Models\Organization;
use App\Support\ActivityLogger;
use App\Support\EffectivePermissionService;
use App\Support\OrganizationMemberResolver;
use App\Support\PersonalMailLinkageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Throwable;

class MemberMailLinkageController extends Controller
{
    public function update(
        UpdateMemberMailLinkageRequest $request,
        Organization $organization,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization($request->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.member-mail-linkage.update'), 403);

        $validated = $request->validated();
        $existingLinkage = MemberMailLinkage::query()
            ->where('organization_member_id', $member->id)
            ->first();

        if (($validated['app_pin'] ?? '') === '' && $existingLinkage === null) {
            throw ValidationException::withMessages([
                'app_pin' => __('Google app pin is required.'),
            ]);
        }

        $payload = [
            'gmail_address' => strtolower($validated['gmail_address']),
            'is_verified' => false,
            'last_tested_at' => null,
        ];

        if (($validated['app_pin'] ?? '') !== '') {
            $payload['app_password'] = $validated['app_pin'];
        }

        MemberMailLinkage::query()->updateOrCreate(
            ['organization_member_id' => $member->id],
            $payload,
        );

        app(ActivityLogger::class)->log(
            $organization->id,
            $member->id,
            $member,
            'member.mail_linkage_updated',
            ['gmail_address' => strtolower($validated['gmail_address'])],
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Gmail linkage saved. Send a test email to verify your app pin.'),
        ]);

        return back();
    }

    public function test(
        Organization $organization,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
        PersonalMailLinkageService $mailLinkageService,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);
        $user = request()->user();

        abort_unless($permissions->memberCan($member, 'org.member-mail-linkage.test'), 403);
        abort_if($user === null || $user->email === null, 422);

        $linkage = MemberMailLinkage::query()
            ->where('organization_member_id', $member->id)
            ->firstOrFail();

        try {
            $mailLinkageService->sendTest($organization, $member, $linkage, $user);

            $linkage->update([
                'is_verified' => true,
                'last_tested_at' => now(),
            ]);

            app(ActivityLogger::class)->log(
                $organization->id,
                $member->id,
                $linkage,
                'member.mail_linkage_verified',
            );

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => __('Test email sent to :email. Gmail linkage verified.', ['email' => $user->email]),
            ]);
        } catch (Throwable $exception) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Gmail test failed: :error', ['error' => $exception->getMessage()]),
            ]);
        }

        return back();
    }
}
