<?php

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventType extends Model
{
    use HasFactory;

    protected $table = 'analytics_event_types';

    protected $fillable = [
        'event_key',
        'label',
        'category',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
