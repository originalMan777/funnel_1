<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'acquisition_contact_id',
        'acquisition_id',
        'service_id',
        'acquisition_path_id',
        'acquisition_path_key',
        'lead_box_id',
        'lead_slot_key',
        'page_key',
        'source_page_key',
        'source_slot_key',
        'source_popup_key',
        'source_url',
        'entry_url',
        'lead_status',
        'type',
        'first_name',
        'email',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function leadBox(): BelongsTo
    {
        return $this->belongsTo(LeadBox::class);
    }

    public function acquisitionContact(): BelongsTo
    {
        return $this->belongsTo(AcquisitionContact::class);
    }

    public function acquisition(): BelongsTo
    {
        return $this->belongsTo(Acquisition::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function acquisitionPath(): BelongsTo
    {
        return $this->belongsTo(AcquisitionPath::class);
    }
}
