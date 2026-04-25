<?php

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyRollup extends Model
{
    use HasFactory;

    protected $table = 'analytics_daily_rollups';

    protected $fillable = [
        'rollup_date',
        'dimension_type',
        'dimension_id',
        'metric_key',
        'metric_value',
    ];

    protected $casts = [
        'rollup_date' => 'date',
        'metric_value' => 'decimal:4',
    ];
}
