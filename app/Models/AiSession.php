<?php

namespace App\Models;

use App\Enums\AiSessionContext;
use App\Enums\AiSessionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiSession extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'organization_member_id',
        'user_id',
        'context',
        'project_id',
        'title',
        'status',
        'last_message_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'context' => AiSessionContext::class,
            'status' => AiSessionStatus::class,
            'last_message_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function organizationMember(): BelongsTo
    {
        return $this->belongsTo(OrganizationMember::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class);
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(AiOnboardingProposal::class);
    }
}
