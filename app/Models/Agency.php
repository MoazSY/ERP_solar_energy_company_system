<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Model;

class Agency extends Model
{
    protected $table = 'agencies';

    protected $fillable = [
        'agency_manager_id',
        'agency_name',
        'agency_logo',
        'commerical_register_number',
        'agency_description',
        'agency_email',
        'agency_phone',
        'tax_number',
        'agency_status',
        'verified_at',
        'working_hours_start',
        'working_hours_end',
    ];

    public function proccess_register()
    {
        return $this->morphMany(Company_Agency_rigester::class, 'registerable');
    }

    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'entity_type');
    }

    public function companyAgencySubscribes(): MorphMany
    {
        return $this->morphMany(Company_agency_subscribe::class, 'subscribable');
    }

    public function customSubscribes(): MorphMany
    {
        return $this->morphMany(Custom_subscribe::class, 'subscribeable');
    }

    public function agencyManager(): BelongsTo
    {
        return $this->belongsTo(Agency_manager::class, 'agency_manager_id');
    }

    public function conflictInvoices(): HasMany
    {
        return $this->hasMany(Conflict_invoice::class, 'agency_id');
    }

    public function paymentsMade(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function paymentsReceived(): MorphMany
    {
        return $this->morphMany(Payment::class, 'target_table');
    }
    public function products(): MorphMany
    {
        return $this->morphMany(Products::class, 'entity_type');

    }
    public function specific_disscounts(): MorphMany
    {
        return $this->morphMany(Specific_disscount::class, 'entity_type');
    }
}
