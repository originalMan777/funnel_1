<?php

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionScenario extends Model
{
    use HasFactory;

    protected $table = 'analytics_session_scenarios';

    public const TYPE_PRIMARY = 'primary';

    public const TYPE_SECONDARY = 'secondary';

    protected $fillable = [
        'session_id',
        'scenario_definition_id',
        'assignment_type',
        'assigned_at',
        'evidence',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'evidence' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function scenarioDefinition(): BelongsTo
    {
        return $this->belongsTo(ScenarioDefinition::class);
    }
}
