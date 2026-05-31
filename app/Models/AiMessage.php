<?php

namespace App\Models;

use App\Enums\AiMessageRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiMessage extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'ai_session_id',
        'role',
        'content',
        'proposed_actions',
        'onboarding_proposal_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => AiMessageRole::class,
            'proposed_actions' => 'array',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AiSession::class, 'ai_session_id');
    }

    public function onboardingProposal(): BelongsTo
    {
        return $this->belongsTo(AiOnboardingProposal::class, 'onboarding_proposal_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AiAuditLog::class);
    }
}
