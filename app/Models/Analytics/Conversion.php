<?php

namespace App\Models\Analytics;

use App\Models\Lead;
use App\Models\LeadBox;
use App\Models\LeadSlot;
use App\Models\Popup;
use App\Models\PopupLead;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Conversion extends Model
{
    use HasFactory;

    protected $table = 'analytics_conversions';

    protected $fillable = [
        'visitor_id',
        'session_id',
        'conversion_type_id',
        'source_type',
        'source_id',
        'lead_id',
        'popup_lead_id',
        'page_id',
        'cta_id',
        'lead_box_id',
        'lead_slot_id',
        'popup_id',
        'occurred_at',
        'properties',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'properties' => 'array',
    ];

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function popupLead(): BelongsTo
    {
        return $this->belongsTo(PopupLead::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function cta(): BelongsTo
    {
        return $this->belongsTo(Cta::class);
    }

    public function leadBox(): BelongsTo
    {
        return $this->belongsTo(LeadBox::class);
    }

    public function leadSlot(): BelongsTo
    {
        return $this->belongsTo(LeadSlot::class);
    }

    public function popup(): BelongsTo
    {
        return $this->belongsTo(Popup::class);
    }

    public function attributions(): HasMany
    {
        return $this->hasMany(ConversionAttribution::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'source_type', 'source_id');
    }
}
