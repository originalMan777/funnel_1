<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcquisitionContact extends Model
{
    use HasFactory;

    protected $table = 'acquisition_contacts';

    protected $fillable = [
        'acquisition_company_id',
        'acquisition_person_id',
        'owner_user_id',
        'contact_type',
        'state',
        'source_type',
        'source_label',
        'normalized_email_key',
        'normalized_phone_key',
        'primary_email',
        'primary_phone',
        'display_name',
        'company_name_snapshot',
        'website_url_snapshot',
        'city_snapshot',
        'state_snapshot',
        'last_activity_at',
        'next_action_at',
        'is_suppressed',
        'suppressed_at',
        'suppression_reason',
        'qualified_at',
        'converted_at',
        'closed_at',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'last_activity_at' => 'datetime',
        'next_action_at' => 'datetime',
        'is_suppressed' => 'boolean',
        'suppressed_at' => 'datetime',
        'qualified_at' => 'datetime',
        'converted_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(AcquisitionCompany::class, 'acquisition_company_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(AcquisitionPerson::class, 'acquisition_person_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function sources(): HasMany
    {
        return $this->hasMany(AcquisitionSource::class, 'acquisition_contact_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(AcquisitionEvent::class, 'acquisition_contact_id');
    }

    public function marketingSyncs(): HasMany
    {
        return $this->hasMany(MarketingContactSync::class, 'acquisition_contact_id');
    }
}
