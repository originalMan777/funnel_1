<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QOPromoRule extends Model
{
    protected $table = 'qo_promo_rules';

    protected $fillable = [
        'qo_item_id',
        'rule_type',
        'lead_box_id',
        'start_question_order',
        'end_question_order',
        'question_order',
        'interval_every',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(QOItem::class, 'qo_item_id');
    }
}
