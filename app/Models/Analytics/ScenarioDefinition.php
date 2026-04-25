<?php

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScenarioDefinition extends Model
{
    use HasFactory;

    protected $table = 'analytics_scenario_definitions';

    protected $fillable = [
        'scenario_key',
        'label',
        'description',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    public function sessionScenarios(): HasMany
    {
        return $this->hasMany(SessionScenario::class);
    }
}
