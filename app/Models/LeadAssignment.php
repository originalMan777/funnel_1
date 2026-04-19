<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeadAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_slot_id',
        'lead_box_id',
        'acquisition_id',
        'service_id',
        'acquisition_path_id',
        'acquisition_path_key',
        'override_title',
        'override_short_text',
        'override_button_text',
    ];

    public function slot(): BelongsTo
    {
        return $this->belongsTo(LeadSlot::class, 'lead_slot_id');
    }

    public function leadBox(): BelongsTo
    {
        return $this->belongsTo(LeadBox::class, 'lead_box_id');
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
