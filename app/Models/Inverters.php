<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inverters extends Model
{
    protected $table = 'inverters';

    protected $fillable = [
        'product_id',
        'grid_type',
        'voltage_v',
        'grid_capacity_kw',
        'solar_capacity_kw',
        'inverter_open',
        'voltage_open',
        'weight_kg',
        'warranty_years',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'product_id');
    }
}
