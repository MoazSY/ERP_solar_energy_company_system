<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Items extends Model
{
    protected $table = 'items';

    protected $fillable = [
        'itemable_type',
        'itemable_id',
        'product_id',
        'item_name_snapshot',
        'quantity',
        'unit_price',
        'total_price',
        'unit_discount_amount',
        'total_discount_amount',
        'discount_type',
        'currency',
        'serial_numbers',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'product_id');
    }
    public function itemable(): MorphTo
    {
        return $this->morphTo(null, 'itemable_type', 'itemable_id');
    }
    public function componentWarranties(): HasMany
    {
        return $this->hasMany(Component_warranties::class, 'item_id');
    }
    public function consumables(): HasMany
    {
        return $this->hasMany(Consumables::class, 'item_id');
    }
    public function productTechicians(): HasMany
    {
        return $this->hasMany(Product_techicians::class, 'item_id');
    }
}
