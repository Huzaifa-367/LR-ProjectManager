<?php

namespace App\Models;

use App\Enums\MailProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizationMailProfile extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'name',
        'provider',
        'is_default',
        'from_name',
        'from_address',
        'reply_to_address',
        'config',
        'is_verified',
        'last_tested_at',
        'is_active',
        'created_by_member_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provider' => MailProvider::class,
            'config' => 'encrypted:array',
            'is_default' => 'boolean',
            'is_verified' => 'boolean',
            'is_active' => 'boolean',
            'last_tested_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function createdByMember(): BelongsTo
    {
        return $this->belongsTo(OrganizationMember::class, 'created_by_member_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class);
    }
}
