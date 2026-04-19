<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunicationDelivery extends Model
{
    use HasFactory;

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'communication_event_id',
        'action_key',
        'channel',
        'provider',
        'recipient_email',
        'recipient_name',
        'subject',
        'status',
        'provider_message_id',
        'error_message',
        'payload',
        'sent_at',
        'communication_template_id',
        'communication_template_version_id',
        'is_test',

    ];

    protected $casts = [
        'payload' => 'array',
        'sent_at' => 'datetime',
        'is_test' => 'boolean',
    ];

    public function communicationEvent(): BelongsTo
    {
        return $this->belongsTo(CommunicationEvent::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CommunicationTemplate::class, 'communication_template_id');
    }

    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(CommunicationTemplateVersion::class, 'communication_template_version_id');
    }

}
