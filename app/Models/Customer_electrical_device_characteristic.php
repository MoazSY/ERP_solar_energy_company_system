<?php

namespace App\Models;

use App\Models\Electrical_device;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Customer_electrical_device_characteristic extends Model
{
    protected $table = 'customer_electrical_device';

    protected $fillable = [
        'customer_id',
        'electrical_device_id',
        'request_solar_system_id',
        'capacity',
        'unit',
        'usage_time',
        'notes',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function electricalDevice(): BelongsTo
    {
        return $this->belongsTo(Electrical_device::class, 'electrical_device_id');
    }

    public function requestSolarSystem(): BelongsTo
    {
        return $this->belongsTo(Request_solar_system::class, 'request_solar_system_id');
    }
}
