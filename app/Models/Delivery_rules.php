<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Delivery_rules extends Model
{
    protected $table = 'delivery_rules';

    protected $fillable = [
        'entity_type_type',
        'entity_type_id',
        'rule_name',
        'governorate_id',
        'area_id',
        'delivery_fee',
        'price_per_km',
        'max_weight_kg',
        'price_per_extra_kg',
        'currency',
        'is_active',
    ];

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorates::class, 'governorate_id');
    }
    public function area(): BelongsTo
    {
        return $this->belongsTo(Areas::class, 'area_id');
    }
    public function entityType(): MorphTo
    {
        return $this->morphTo(null, 'entity_type_type', 'entity_type_id');
    }
}
