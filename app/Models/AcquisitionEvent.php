<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcquisitionEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'acquisition_contact_id',
        'acquisition_company_id',
        'acquisition_person_id',
        'acquisition_campaign_id',
        'acquisition_touch_id',
        'event_type',
        'channel',
        'actor_type',
        'actor_user_id',
        'related_table',
        'related_id',
        'summary',
        'details',
        'occurred_at',
    ];

    protected $casts = [
        'details' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(AcquisitionContact::class, 'acquisition_contact_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(AcquisitionCompany::class, 'acquisition_company_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(AcquisitionPerson::class, 'acquisition_person_id');
    }
}
