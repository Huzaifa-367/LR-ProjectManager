<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberDailyFocus extends Model
{
    protected $table = 'member_daily_focus';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_member_id',
        'task_id',
        'focus_date',
        'sort_order',
        'is_auto',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'focus_date' => 'date',
            'is_auto' => 'boolean',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(OrganizationMember::class, 'organization_member_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
