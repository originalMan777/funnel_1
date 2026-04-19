<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcquisitionCompany extends Model
{
    use HasFactory;

    protected $table = 'acquisition_companies';

    protected $fillable = [
        'name',
        'website_url',
        'domain',
        'industry',
        'sub_industry',
        'city',
        'state',
        'country_code',
        'phone',
        'status',
        'fit_score',
        'data_source',
        'notes',
        'created_by',
        'updated_by',
    ];

    public function people(): HasMany
    {
        return $this->hasMany(AcquisitionPerson::class, 'acquisition_company_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(AcquisitionContact::class, 'acquisition_company_id');
    }
}
