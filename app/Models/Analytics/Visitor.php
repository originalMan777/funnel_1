<?php

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Visitor extends Model
{
    use HasFactory;

    protected $table = 'analytics_visitors';

    protected $fillable = [
        'visitor_key',
        'first_seen_at',
        'last_seen_at',
        'first_user_agent_hash',
        'latest_user_agent_hash',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
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
}
