<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunicationTemplate extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ARCHIVED = 'archived';

    public const CHANNEL_EMAIL = 'email';

    public const CATEGORY_TRANSACTIONAL = 'transactional';

    protected $fillable = [
        'key',
        'name',
        'channel',
        'category',
        'status',
        'description',
        'from_name_override',
        'from_email_override',
        'reply_to_email',
        'current_version_id',
        'created_by',
        'updated_by',
    ];

    public function versions(): HasMany
    {
        return $this->hasMany(CommunicationTemplateVersion::class);
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(CommunicationTemplateVersion::class, 'current_version_id');
    }

    public function bindings(): HasMany
    {
        return $this->hasMany(CommunicationTemplateBinding::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(CommunicationDelivery::class, 'communication_template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeEmail($query)
    {
        return $query->where('channel', self::CHANNEL_EMAIL);
    }

    public function scopeTransactional($query)
    {
        return $query->where('category', self::CATEGORY_TRANSACTIONAL);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeArchived($query)
    {
        return $query->where('status', self::STATUS_ARCHIVED);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }

    public function hasPublishedCurrentVersion(): bool
    {
        return $this->currentVersion !== null && $this->currentVersion->is_published;
    }
}
