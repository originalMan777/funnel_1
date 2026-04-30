<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QOSubmission extends Model
{
    protected $table = 'qo_submissions';

    protected $fillable = [
        'qo_item_id',
        'lead_id',
        'session_uuid',
        'status',
        'is_preview',
        'current_question_id',
        'final_score',
        'final_category_key',
        'final_outcome_key',
        'outcome_snapshot_json',
        'source_url',
        'referrer_url',
        'utm_json',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'is_preview' => 'boolean',
        'outcome_snapshot_json' => 'array',
        'utm_json' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(QOItem::class, 'qo_item_id');
    }

    public function currentQuestion(): BelongsTo
    {
        return $this->belongsTo(QOQuestion::class, 'current_question_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QOAnswer::class, 'qo_submission_id')->orderBy('id');
    }

    public function captures(): HasMany
    {
        return $this->hasMany(QOCapture::class, 'qo_submission_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
