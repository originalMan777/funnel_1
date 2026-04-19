<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcquisitionPath extends Model
{
    use HasFactory;

    protected $fillable = [
        'acquisition_id',
        'service_id',
        'name',
        'slug',
        'path_key',
        'entry_type',
        'source_context',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function acquisition(): BelongsTo
    {
        return $this->belongsTo(Acquisition::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
