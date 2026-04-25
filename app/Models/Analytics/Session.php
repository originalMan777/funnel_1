<?php

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Session extends Model
{
    use HasFactory;

    protected $table = 'analytics_sessions';

    protected $fillable = [
        'session_key',
        'visitor_id',
        'started_at',
        'ended_at',
        'entry_page_id',
        'entry_url',
        'entry_path',
        'referrer_url',
        'referrer_host',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'device_type_id',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    public function entryPage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'entry_page_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function attributionTouches(): HasMany
    {
        return $this->hasMany(AttributionTouch::class);
    }

    public function conversions(): HasMany
    {
        return $this->hasMany(Conversion::class);
    }

    public function scenarioAssignment(): HasOne
    {
        return $this->hasOne(SessionScenario::class)->where('assignment_type', SessionScenario::TYPE_PRIMARY);
    }

    public function scenarioAssignments(): HasMany
    {
        return $this->hasMany(SessionScenario::class);
    }

    public function secondaryScenarioAssignments(): HasMany
    {
        return $this->hasMany(SessionScenario::class)->where('assignment_type', SessionScenario::TYPE_SECONDARY);
    }
}
