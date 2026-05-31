<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationRolePermission extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_role_id',
        'permission',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(OrganizationRole::class, 'organization_role_id');
    }
}
