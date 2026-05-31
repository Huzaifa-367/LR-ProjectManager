<?php

namespace App\Models;

use App\Enums\OrganizationMemberStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrganizationMember extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'user_id',
        'organization_role_id',
        'display_name',
        'email',
        'title',
        'status',
        'is_primary_org',
        'sort_order',
        'invited_at',
        'joined_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrganizationMemberStatus::class,
            'is_primary_org' => 'boolean',
            'invited_at' => 'datetime',
            'joined_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(OrganizationRole::class, 'organization_role_id');
    }

    public function isActive(): bool
    {
        return $this->status === OrganizationMemberStatus::Active;
    }

    public function projectMembers(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function assignedTasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_assignees')
            ->withPivot(['is_primary', 'assigned_at', 'assigned_by_member_id'])
            ->withTimestamps();
    }

    public function mailLinkage(): HasOne
    {
        return $this->hasOne(MemberMailLinkage::class);
    }
}
