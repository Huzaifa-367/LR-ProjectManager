<?php

namespace App\Models;

use App\Enums\OnboardingProposalStatus;
use App\Enums\OnboardingProposalType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiOnboardingProposal extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'ai_session_id',
        'organization_id',
        'created_by_member_id',
        'proposal_type',
        'status',
        'payload',
        'summary',
        'project_id',
        'applied_at',
        'applied_by_member_id',
        'rejection_reason',
        'version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'proposal_type' => OnboardingProposalType::class,
            'status' => OnboardingProposalStatus::class,
            'payload' => 'array',
            'applied_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AiSession::class, 'ai_session_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function createdByMember(): BelongsTo
    {
        return $this->belongsTo(OrganizationMember::class, 'created_by_member_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function appliedByMember(): BelongsTo
    {
        return $this->belongsTo(OrganizationMember::class, 'applied_by_member_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class, 'onboarding_proposal_id');
    }
}
