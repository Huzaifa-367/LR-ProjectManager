<?php

namespace App\Models;

use App\Enums\DeadlineType;
use App\Enums\PriorityLevel;
use App\Enums\TaskKind;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'project_id',
        'kind',
        'title',
        'description',
        'created_by_member_id',
        'priority',
        'status',
        'deadline_type',
        'deadline_date',
        'deadline_label',
        'external_link',
        'is_done',
        'completed_at',
        'completed_by_member_id',
        'meta',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => TaskKind::class,
            'priority' => PriorityLevel::class,
            'status' => TaskStatus::class,
            'deadline_type' => DeadlineType::class,
            'deadline_date' => 'date',
            'is_done' => 'boolean',
            'completed_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function createdByMember(): BelongsTo
    {
        return $this->belongsTo(OrganizationMember::class, 'created_by_member_id');
    }

    public function completedByMember(): BelongsTo
    {
        return $this->belongsTo(OrganizationMember::class, 'completed_by_member_id');
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(OrganizationMember::class, 'task_assignees')
            ->withPivot(['is_primary', 'assigned_at', 'assigned_by_member_id'])
            ->withTimestamps();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeOfKind(Builder $query, TaskKind $kind): Builder
    {
        return $query->where('kind', $kind->value);
    }
}
