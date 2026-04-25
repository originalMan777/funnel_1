<?php

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversionAttribution extends Model
{
    use HasFactory;

    protected $table = 'analytics_conversion_attributions';

    protected $fillable = [
        'conversion_id',
        'session_id',
        'visitor_id',
        'attribution_touch_id',
        'landing_page_id',
        'attribution_scope',
        'source_key',
        'source_label',
        'referrer_host',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'attribution_method',
        'attribution_confidence',
        'occurred_at',
        'properties',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'properties' => 'array',
        'attribution_confidence' => 'decimal:4',
    ];

    public function conversion(): BelongsTo
    {
        return $this->belongsTo(Conversion::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    public function attributionTouch(): BelongsTo
    {
        return $this->belongsTo(AttributionTouch::class);
    }

    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'landing_page_id');
    }
}
