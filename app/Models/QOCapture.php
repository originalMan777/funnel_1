<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QOCapture extends Model
{
    protected $table = 'qo_captures';

    protected $fillable = [
        'qo_item_id',
        'qo_submission_id',
        'stage',
        'name',
        'email',
        'phone',
        'payload_json',
        'is_preview',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'is_preview' => 'boolean',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(QOItem::class, 'qo_item_id');
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(QOSubmission::class, 'qo_submission_id');
    }
}
