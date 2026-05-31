<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Enums\NotificationEventType;
use App\Enums\ScheduledNotificationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ScheduledNotification extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'organization_member_id',
        'event_type',
        'channel',
        'subject_type',
        'subject_id',
        'trigger_at',
        'payload',
        'dedupe_key',
        'status',
        'notification_delivery_id',
        'cancelled_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_type' => NotificationEventType::class,
            'channel' => NotificationChannel::class,
            'status' => ScheduledNotificationStatus::class,
            'payload' => 'array',
            'trigger_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function organizationMember(): BelongsTo
    {
        return $this->belongsTo(OrganizationMember::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function notificationDelivery(): BelongsTo
    {
        return $this->belongsTo(NotificationDelivery::class);
    }
}
