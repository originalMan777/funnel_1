<?php

namespace App\Models\Analytics;

use App\Models\LeadBox;
use App\Models\LeadSlot;
use App\Models\Popup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Event extends Model
{
    use HasFactory;

    protected $table = 'analytics_events';

    public const UPDATED_AT = null;

    protected $fillable = [
        'visitor_id',
        'session_id',
        'event_type_id',
        'page_id',
        'cta_id',
        'lead_box_id',
        'lead_slot_id',
        'popup_id',
        'surface_id',
        'subject_type',
        'subject_id',
        'occurred_at',
        'properties',
        'created_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'created_at' => 'datetime',
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

    public function eventType(): BelongsTo
    {
        return $this->belongsTo(EventType::class);
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

    public function surface(): BelongsTo
    {
        return $this->belongsTo(Surface::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
