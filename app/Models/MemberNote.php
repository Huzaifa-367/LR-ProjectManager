<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberNote extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_member_id',
        'body',
        'sort_order',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(OrganizationMember::class, 'organization_member_id');
    }
}
