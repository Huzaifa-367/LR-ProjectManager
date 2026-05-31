<?php

namespace App\Models;

use App\Enums\InvitationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationInvitation extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'email',
        'organization_role_id',
        'invited_by_member_id',
        'token_hash',
        'status',
        'expires_at',
        'accepted_at',
        'organization_member_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => InvitationStatus::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(OrganizationRole::class, 'organization_role_id');
    }

    public function invitedByMember(): BelongsTo
    {
        return $this->belongsTo(OrganizationMember::class, 'invited_by_member_id');
    }

    public function organizationMember(): BelongsTo
    {
        return $this->belongsTo(OrganizationMember::class);
    }
}
