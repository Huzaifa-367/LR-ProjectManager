<?php

namespace App\Http\Controllers;

use App\Enums\NotificationChannel;
use App\Enums\NotificationEventType;
use App\Http\Requests\Organizations\UpdateNotificationPreferencesRequest;
use App\Models\MemberMailLinkage;
use App\Models\MemberNotificationPreference;
use App\Models\Organization;
use App\Support\CommandCentreResourcePresenter;
use App\Support\EffectivePermissionService;
use App\Support\MemberNotificationPreferenceSeeder;
use App\Support\OrganizationMemberResolver;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class NotificationPreferenceController extends Controller
{
    public function show(
        Organization $organization,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
        MemberNotificationPreferenceSeeder $preferenceSeeder,
    ): Response {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.notification-preferences.show'), 403);

        $preferenceSeeder->seedForMember($member);

        $preferences = MemberNotificationPreference::query()
            ->where('organization_member_id', $member->id)
            ->get()
            ->mapWithKeys(fn (MemberNotificationPreference $preference): array => [
                $preference->event_type->value.'.'.$preference->channel->value => $preference->is_enabled,
            ])
            ->all();

        $matrix = collect(NotificationEventType::cases())
            ->map(fn (NotificationEventType $eventType): array => [
                'event_type' => $eventType->value,
                'label' => $eventType->label(),
                'channels' => collect(NotificationChannel::cases())
                    ->mapWithKeys(fn (NotificationChannel $channel): array => [
                        $channel->value => $preferences[$eventType->value.'.'.$channel->value] ?? true,
                    ])
                    ->all(),
            ])
            ->values()
            ->all();

        $mailLinkage = MemberMailLinkage::query()
            ->where('organization_member_id', $member->id)
            ->first();

        return Inertia::render('organizations/settings/notifications', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'matrix' => $matrix,
            'mailLinkage' => [
                'gmail_address' => $mailLinkage?->gmail_address,
                'is_verified' => $mailLinkage?->is_verified ?? false,
                'last_tested_at' => $mailLinkage?->last_tested_at?->toIso8601String(),
                'has_app_pin' => $mailLinkage !== null,
            ],
            'permissions' => CommandCentreResourcePresenter::permissions($permissions, $member),
        ]);
    }

    public function update(
        UpdateNotificationPreferencesRequest $request,
        Organization $organization,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization($request->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.notification-preferences.update'), 403);

        foreach ($request->validated('preferences') as $preference) {
            MemberNotificationPreference::query()->updateOrCreate(
                [
                    'organization_member_id' => $member->id,
                    'event_type' => $preference['event_type'],
                    'channel' => $preference['channel'],
                ],
                [
                    'is_enabled' => (bool) $preference['is_enabled'],
                ],
            );
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Notification preferences saved.')]);

        return back();
    }
}
