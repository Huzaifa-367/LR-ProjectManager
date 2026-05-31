<?php

namespace App\Support;

use App\Enums\NotificationChannel;
use App\Enums\NotificationEventType;
use App\Models\MemberNotificationPreference;
use App\Models\OrganizationMember;

final class MemberNotificationPreferenceSeeder
{
    public function seedForMember(OrganizationMember $member): void
    {
        foreach (NotificationEventType::cases() as $eventType) {
            foreach (NotificationChannel::cases() as $channel) {
                MemberNotificationPreference::query()->firstOrCreate(
                    [
                        'organization_member_id' => $member->id,
                        'event_type' => $eventType->value,
                        'channel' => $channel->value,
                    ],
                    [
                        'is_enabled' => true,
                    ],
                );
            }
        }
    }
}
