<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcquisitionPerson extends Model
{
    use HasFactory;

    protected $table = 'acquisition_people';

    protected $fillable = [
        'acquisition_company_id',
        'full_name',
        'first_name',
        'last_name',
        'job_title',
        'email',
        'phone',
        'is_primary_contact',
        'linkedin_url',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_primary_contact' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(AcquisitionCompany::class, 'acquisition_company_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(AcquisitionContact::class, 'acquisition_person_id');
    }
}
