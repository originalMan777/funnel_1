<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignEnrollment extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_EXITED = 'exited';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'campaign_id',
        'lead_id',
        'popup_lead_id',
        'acquisition_contact_id',
        'current_step_order',
        'status',
        'next_run_at',
        'started_at',
        'completed_at',
        'exit_reason',
    ];

    protected $casts = [
        'next_run_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function popupLead(): BelongsTo
    {
        return $this->belongsTo(PopupLead::class);
    }

    public function acquisitionContact(): BelongsTo
    {
        return $this->belongsTo(AcquisitionContact::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now());
    }

    public function markCompleted(?string $reason = 'completed'): void
    {
        $this->forceFill([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'exit_reason' => $reason,
            'next_run_at' => null,
        ])->save();
    }

    public function markExited(string $reason): void
    {
        $this->forceFill([
            'status' => self::STATUS_EXITED,
            'completed_at' => now(),
            'exit_reason' => $reason,
            'next_run_at' => null,
        ])->save();
    }

    public function markFailed(string $reason): void
    {
        $this->forceFill([
            'status' => self::STATUS_FAILED,
            'exit_reason' => $reason,
        ])->save();
    }
}
