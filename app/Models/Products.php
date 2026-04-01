<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Products extends Model
{
    protected $table = 'products';

    protected $fillable = [
        'entity_type_type',
        'entity_type_id',
        'product_name',
        'product_type',
        'product_brand',
        'model_number',
        'quentity',
        'price',
        'disscount_type',
        'disscount_value',
        'currency',
        'manufacture_date',
        'product_image',
    ];

    public function entityType(): MorphTo
    {
        return $this->morphTo(null, 'entity_type_type', 'entity_type_id');
    }
    public function items(): HasMany
    {
        return $this->hasMany(Items::class, 'product_id');
    }
    public function inverters(): HasMany
    {
        return $this->hasMany(Inverters::class, 'product_id');
    }
    public function batteries(): HasMany
    {
        return $this->hasMany(Batteries::class, 'product_id');
    }
    public function specificDisscounts(): HasMany
    {
        return $this->hasMany(Specific_disscount::class, 'product_id');
    }
    public function solarPanals(): HasMany
    {
        return $this->hasMany(Solar_panal::class, 'product_id');
    }
}
