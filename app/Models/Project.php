<?php

namespace App\Models;

use App\Enums\ProjectHealth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'name',
        'objective',
        'progress_percent',
        'next_action',
        'health',
        'owner_member_id',
        'created_by_member_id',
        'sort_order',
        'archived_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'health' => ProjectHealth::class,
            'progress_percent' => 'integer',
            'archived_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function ownerMember(): BelongsTo
    {
        return $this->belongsTo(OrganizationMember::class, 'owner_member_id');
    }

    public function createdByMember(): BelongsTo
    {
        return $this->belongsTo(OrganizationMember::class, 'created_by_member_id');
    }

    public function roles(): HasMany
    {
        return $this->hasMany(ProjectRole::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }
}
