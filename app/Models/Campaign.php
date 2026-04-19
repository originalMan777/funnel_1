<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_ARCHIVED = 'archived';

    public const AUDIENCE_LEADS = 'leads';
    public const AUDIENCE_POPUP_LEADS = 'popup_leads';
    public const AUDIENCE_ACQUISITION_CONTACTS = 'acquisition_contacts';

    protected $fillable = [
        'name',
        'status',
        'audience_type',
        'entry_trigger',
        'description',
        'created_by',
        'updated_by',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(CampaignStep::class)->orderBy('step_order');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(CampaignEnrollment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeForTrigger(Builder $query, string $trigger): Builder
    {
        return $query->where('entry_trigger', $trigger);
    }
}
