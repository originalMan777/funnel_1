<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcquisitionSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'acquisition_contact_id',
        'source_type',
        'source_table',
        'source_record_id',
        'page_key',
        'source_url',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'referrer_url',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(AcquisitionContact::class, 'acquisition_contact_id');
    }
}
