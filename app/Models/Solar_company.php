<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Model;

class Solar_company extends Model
{
    protected $table = 'solar_companies';

    protected $fillable = [
        'solar_company_manager_id',
        'company_name',
        'company_logo',
        'commerical_register_number',
        'company_description',
        'company_email',
        'company_phone',
        'tax_number',
        'company_status',
        'verified_at',
        'working_hours_start',
        'working_hours_end',
    ];

    public function proccess_register()
    {
        return $this->morphMany(Company_Agency_rigester::class, 'registerable');
    }

    public function solarCompanyManager(): BelongsTo
    {
        return $this->belongsTo(Solar_company_manager::class, 'solar_company_manager_id');
    }

    public function projectTasks(): HasMany
    {
        return $this->hasMany(Project_task::class, 'company_id');
    }

    public function inputOutputRequests(): HasMany
    {
        return $this->hasMany(Input_output_request::class, 'company_id');
    }

    public function componentWarranties(): HasMany
    {
        return $this->hasMany(Component_warranties::class, 'company_id');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offers::class, 'company_id');
    }

    public function metalInstallationRules(): HasMany
    {
        return $this->hasMany(Metal_installation_rules::class, 'company_id');
    }

    public function companyProtofolios(): HasMany
    {
        return $this->hasMany(Company_protofolio::class, 'company_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'company_id');
    }

    public function conflictInvoices(): HasMany
    {
        return $this->hasMany(Conflict_invoice::class, 'company_id');
    }

    public function installationRules(): HasMany
    {
        return $this->hasMany(Installation_rules::class, 'company_id');
    }

    public function projectWarranties(): HasMany
    {
        return $this->hasMany(Project_warranties::class, 'company_id');
    }

    public function requestSolarSystems(): HasMany
    {
        return $this->hasMany(Request_solar_system::class, 'company_id');
    }

    public function metainenceRequests(): HasMany
    {
        return $this->hasMany(Metainence_request::class, 'company_id');
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

    public function paymentsMade(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function paymentsReceived(): MorphMany
    {
        return $this->morphMany(Payment::class, 'target_table');
    }

    public function deliveryRules(): MorphMany
    {
        return $this->morphMany(Delivery_rules::class, 'entity_type');
    }

    public function products(): MorphMany
    {
        return $this->morphMany(Products::class, 'entity_type');
    }

    public function Order_list(): MorphMany
    {
        return $this->morphMany(Order_list::class, 'request_entity');
    }

    public function Employment_orders(): MorphMany
    {
        return $this->morphMany(Employment_orders::class, 'entity_type');
    }
}
