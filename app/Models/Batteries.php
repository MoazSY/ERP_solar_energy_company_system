<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Batteries extends Model
{
    protected $table = 'batteries';

    protected $fillable = [
        'product_id',
        'battery_type',
        'capacity_kwh',
        'voltage_v',
        'cycle_life',
        'warranty_years',
        'weight_kg',
        'Amperage_Ah',
        'celles_type',
        'celles_name',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'product_id');
    }
}
