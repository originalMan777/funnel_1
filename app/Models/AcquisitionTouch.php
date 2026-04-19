<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcquisitionTouch extends Model
{
    use HasFactory;

    protected $fillable = [
        'acquisition_contact_id',
        'acquisition_campaign_id',
        'acquisition_sequence_id',
        'acquisition_sequence_step_id',
        'owner_user_id',
        'touch_type',
        'status',
        'scheduled_for',
        'approved_at',
        'sent_at',
        'completed_at',
        'failed_at',
        'subject',
        'body',
        'recipient_email',
        'recipient_phone',
        'provider',
        'provider_message_id',
        'failure_reason',
        'response_detected_at',
        'metadata',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'approved_at' => 'datetime',
        'sent_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'response_detected_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(AcquisitionContact::class, 'acquisition_contact_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }
}
