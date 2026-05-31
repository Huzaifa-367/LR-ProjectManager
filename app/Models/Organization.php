<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'logo_path',
        'owner_user_id',
        'settings',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(OrganizationMember::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(OrganizationRole::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(OrganizationInvitation::class);
    }

    public function mailProfiles(): HasMany
    {
        return $this->hasMany(OrganizationMailProfile::class);
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultSettings(?string $timezone = null): array
    {
        return [
            'focus_cap' => 10,
            'timezone' => $timezone ?? config('app.timezone', 'UTC'),
            'auto_focus_enabled' => true,
            'auto_focus_for' => 'assignee',
            'notifications' => [
                'task_due_reminder_days' => [1, 0],
                'task_due_reminder_time' => '08:00',
                'overdue_reminder_enabled' => true,
                'overdue_reminder_time' => '09:00',
                'daily_digest_enabled' => true,
                'daily_digest_time' => '07:00',
                'daily_digest_days' => ['mon', 'tue', 'wed', 'thu', 'fri'],
            ],
            'ai_enabled' => true,
            'ai_assign_pending_invites' => false,
        ];
    }
}
