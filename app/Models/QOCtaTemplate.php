<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QOCtaTemplate extends Model
{
    protected $table = 'qo_cta_templates';
    protected $fillable = [
        'qo_item_id',
        'slot',
        'headline',
        'body',
        'button_label',
        'button_url',
        'secondary_button_label',
        'secondary_button_url',
        'is_enabled',
        'priority',
        'display_rules',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'display_rules' => 'array',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(QOItem::class, 'qo_item_id');
    }
}
