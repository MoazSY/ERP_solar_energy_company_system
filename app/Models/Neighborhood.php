<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Neighborhood extends Model
{
    protected $table = 'neighborhoods';

    protected $fillable = [
        'name',
        'area_id',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Areas::class, 'area_id');
    }
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class, 'neighborhood_id');
    }
}
