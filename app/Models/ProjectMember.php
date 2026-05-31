<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMember extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'organization_member_id',
        'project_role_id',
        'joined_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function organizationMember(): BelongsTo
    {
        return $this->belongsTo(OrganizationMember::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(ProjectRole::class, 'project_role_id');
    }
}
