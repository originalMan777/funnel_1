<?php

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributionTouch extends Model
{
    use HasFactory;

    protected $table = 'analytics_attribution_touches';

    protected $fillable = [
        'visitor_id',
        'session_id',
        'landing_page_id',
        'landing_url',
        'referrer_url',
        'referrer_host',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'attribution_method',
        'attribution_confidence',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'attribution_confidence' => 'decimal:4',
    ];

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'landing_page_id');
    }
}
