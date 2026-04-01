<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Request_solar_system extends Model
{
    protected $table = 'request_solar_systems';

    protected $fillable = [
        'customer_id',
        'company_id',
        'requested_capacity_kw',
        'dayly_consumption_kwh',
        'nightly_consumption_kwh',
        'system_type',
        'invertar_type',
        'inverter_brand',
        'battery_type',
        'battery_brand',
        'solar_panel_type',
        'solar_panel_brand',
        'inverter_capacity_kw',
        'solar_panel_capacity_kw',
        'solar_panel_number',
        'battery_capacity_kwh',
        'battery_number',
        'inverter_voltage_v',
        'battery_voltage_v',
        'expected_budget',
        'metal_base_type',
        'front_base_height_m',
        'back_base_height_m',
        'surface_image',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
    public function company(): BelongsTo
    {
        return $this->belongsTo(Solar_company::class, 'company_id');
    }
}
