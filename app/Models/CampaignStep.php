<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignStep extends Model
{
    public const DELAY_UNIT_DAYS = 'days';

    public const SEND_MODE_TEMPLATE = 'template';
    public const SEND_MODE_CUSTOM = 'custom';

    protected $fillable = [
        'campaign_id',
        'step_order',
        'delay_amount',
        'delay_unit',
        'send_mode',
        'template_id',
        'subject',
        'html_body',
        'text_body',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CommunicationTemplate::class, 'template_id');
    }

    public function isTemplateMode(): bool
    {
        return $this->send_mode === self::SEND_MODE_TEMPLATE;
    }

    public function isCustomMode(): bool
    {
        return $this->send_mode === self::SEND_MODE_CUSTOM;
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }
}
