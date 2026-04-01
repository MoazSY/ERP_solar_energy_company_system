<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Areas extends Model
{
    protected $table = 'areas';

    protected $fillable = [
        'name',
        'governorate_id',
    ];

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorates::class, 'governorate_id');
    }
    public function deliveryRules(): HasMany
    {
        return $this->hasMany(Delivery_rules::class, 'area_id');
    }
    public function deliveries(): HasMany
    {
        return $this->hasMany(Deliveries::class, 'area_id');
    }
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class, 'area_id');
    }
    public function neighborhoods(): HasMany
    {
        return $this->hasMany(Neighborhood::class, 'area_id');
    }
}
