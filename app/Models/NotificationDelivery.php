<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use App\Enums\NotificationChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotificationDelivery extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'organization_mail_profile_id',
        'organization_member_id',
        'recipient_user_id',
        'recipient_email',
        'channel',
        'notification_class',
        'event_type',
        'subject',
        'payload',
        'subject_type',
        'subject_id',
        'status',
        'provider_message_id',
        'error_message',
        'attempts',
        'queued_at',
        'sent_at',
        'failed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'status' => DeliveryStatus::class,
            'payload' => 'array',
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function mailProfile(): BelongsTo
    {
        return $this->belongsTo(OrganizationMailProfile::class, 'organization_mail_profile_id');
    }

    public function organizationMember(): BelongsTo
    {
        return $this->belongsTo(OrganizationMember::class);
    }

    public function recipientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
