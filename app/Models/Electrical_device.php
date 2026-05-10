<?php

namespace App\Models;

use App\Models\Customer_electrical_device_characteristic;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Electrical_device extends Model
{
    protected $table = 'electrical_devices';

    protected $fillable = [
        'name',
    ];

    public function customerElectricalDeviceCharacteristics(): HasMany
    {
        return $this->hasMany(Customer_electrical_device_characteristic::class, 'electrical_device_id');
    }
}
