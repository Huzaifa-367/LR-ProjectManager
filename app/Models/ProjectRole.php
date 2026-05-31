<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectRole extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'name',
        'slug',
        'is_default',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(ProjectRolePermission::class);
    }

    /** @return list<string> */
    public function permissionSlugs(): array
    {
        return $this->permissions()->pluck('permission')->all();
    }
}
