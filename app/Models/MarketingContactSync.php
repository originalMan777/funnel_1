<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingContactSync extends Model
{
    use HasFactory;

    public const STATUS_SYNCED = 'synced';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'acquisition_contact_id',
        'provider',
        'audience_key',
        'email',
        'external_contact_id',
        'last_sync_status',
        'last_error_message',
        'metadata',
        'last_synced_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function acquisitionContact(): BelongsTo
    {
        return $this->belongsTo(AcquisitionContact::class);
    }
}
