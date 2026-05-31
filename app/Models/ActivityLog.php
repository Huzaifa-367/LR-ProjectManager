<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'actor_member_id',
        'subject_type',
        'subject_id',
        'event',
        'properties',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function actorMember(): BelongsTo
    {
        return $this->belongsTo(OrganizationMember::class, 'actor_member_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
