<?php

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Surface extends Model
{
    use HasFactory;

    protected $table = 'analytics_surfaces';

    protected $fillable = [
        'surface_key',
        'label',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
