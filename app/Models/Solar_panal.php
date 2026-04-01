<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Solar_panal extends Model
{
    protected $table = 'solar_panals';

    protected $fillable = [
        'product_id',
        'capacity_kw',
        'basbar_number',
        'is_half_cell',
        'is_bifacial',
        'warranty_years',
        'weight_kg',
        'length_m',
        'width_m',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'product_id');
    }
}
