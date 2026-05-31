<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberMailLinkage extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_member_id',
        'gmail_address',
        'app_password',
        'is_verified',
        'last_tested_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'app_password' => 'encrypted',
            'is_verified' => 'boolean',
            'last_tested_at' => 'datetime',
        ];
    }

    public function organizationMember(): BelongsTo
    {
        return $this->belongsTo(OrganizationMember::class);
    }
}
