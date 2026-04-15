<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Specific_disscount extends Model
{
    protected $table = 'specific_disscounts';

    protected $fillable = [
        'product_id',
        'entity_type_type',
        'entity_type_id',
        'discount_type_type',
        'discount_type_id',
        'discount_amount',
        'disscount_type',
        'currency',
        'product_type',
        'product_brand',
        'disscount_active',
        'quentity_condition',
        'public',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'product_id');
    }
    public function entity_type(): MorphTo
    {
        return $this->morphTo(null, 'entity_type_type', 'entity_type_id');
    }
    public function discountType(): MorphTo
    {
        return $this->morphTo(null, 'discount_type_type', 'discount_type_id');
    }
}
