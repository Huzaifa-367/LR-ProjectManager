<?php

namespace App\Models;

use App\Enums\ExportJobStatus;
use App\Enums\ExportType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExportJob extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'requested_by_member_id',
        'export_type',
        'filters',
        'status',
        'disk',
        'path',
        'error_message',
        'expires_at',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'export_type' => ExportType::class,
            'filters' => 'array',
            'status' => ExportJobStatus::class,
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function requestedByMember(): BelongsTo
    {
        return $this->belongsTo(OrganizationMember::class, 'requested_by_member_id');
    }

    public function isDownloadable(): bool
    {
        return $this->status === ExportJobStatus::Completed
            && $this->path !== null
            && $this->expires_at->isFuture();
    }
}
