<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Governorates extends Model
{
    protected $table = 'governorates';

    protected $fillable = [
        'name',
    ];

    public function areas(): HasMany
    {
        return $this->hasMany(Areas::class, 'governorate_id');
    }
    public function deliveryRules(): HasMany
    {
        return $this->hasMany(Delivery_rules::class, 'governorate_id');
    }
    public function deliveries(): HasMany
    {
        return $this->hasMany(Deliveries::class, 'governorate_id');
    }
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class, 'governorate_id');
    }
}
