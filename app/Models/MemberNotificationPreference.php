<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Enums\NotificationEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberNotificationPreference extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_member_id',
        'event_type',
        'channel',
        'is_enabled',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_type' => NotificationEventType::class,
            'channel' => NotificationChannel::class,
            'is_enabled' => 'boolean',
        ];
    }

    public function organizationMember(): BelongsTo
    {
        return $this->belongsTo(OrganizationMember::class);
    }
}
