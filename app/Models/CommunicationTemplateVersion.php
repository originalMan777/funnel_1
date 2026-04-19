<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunicationTemplateVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'communication_template_id',
        'version_number',
        'subject',
        'preview_text',
        'headline',
        'html_body',
        'text_body',
        'variables_schema',
        'sample_payload',
        'notes',
        'is_published',
        'published_at',
        'created_by',
    ];

    protected $casts = [
        'variables_schema' => 'array',
        'sample_payload' => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(CommunicationTemplate::class, 'communication_template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(CommunicationDelivery::class, 'communication_template_version_id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeUnpublished($query)
    {
        return $query->where('is_published', false);
    }

    public function isPublished(): bool
    {
        return (bool) $this->is_published;
    }
}
