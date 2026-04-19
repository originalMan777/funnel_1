<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunicationTemplateBinding extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_key',
        'channel',
        'action_key',
        'communication_template_id',
        'is_enabled',
        'priority',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'priority' => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(CommunicationTemplate::class, 'communication_template_id');
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeDisabled($query)
    {
        return $query->where('is_enabled', false);
    }

    public function scopeEmail($query)
    {
        return $query->where('channel', CommunicationTemplate::CHANNEL_EMAIL);
    }

    public function isEnabled(): bool
    {
        return (bool) $this->is_enabled;
    }
}
