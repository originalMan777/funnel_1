<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PopupLead extends Model
{
    protected $fillable = [
        'acquisition_contact_id',
        'popup_id',
        'page_key',
        'source_url',
        'lead_type',
        'name',
        'email',
        'phone',
        'message',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function acquisitionContact(): BelongsTo
    {
        return $this->belongsTo(AcquisitionContact::class);
    }
}
