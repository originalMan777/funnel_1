<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QOOutcome extends Model
{
    protected $table = 'qo_outcomes';

    protected $fillable = [
        'qo_item_id',
        'outcome_key',
        'title',
        'summary',
        'body',
        'sort_order',
        'min_score',
        'max_score',
        'category_key',
        'cta_label',
        'cta_url',
        'lead_box_id',
        'meta_json',
    ];

    protected $casts = [
        'meta_json' => 'array',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(QOItem::class, 'qo_item_id');
    }
}
