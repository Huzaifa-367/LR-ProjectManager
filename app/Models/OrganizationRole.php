<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizationRole extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'name',
        'slug',
        'description',
        'is_system',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(OrganizationRolePermission::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(OrganizationMember::class, 'organization_role_id');
    }

    /** @return list<string> */
    public function permissionSlugs(): array
    {
        return $this->permissions()->pluck('permission')->all();
    }
}
