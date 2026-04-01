<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Address extends Model
{
    protected $table = 'addresses';

    protected $fillable = [
        'entity_type_type',
        'entity_type_id',
        'governorate_id',
        'area_id',
        'neighborhood_id',
        'address_description',
        'latitude',
        'longitude',
    ];

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorates::class, 'governorate_id');
    }
    public function area(): BelongsTo
    {
        return $this->belongsTo(Areas::class, 'area_id');
    }
    public function neighborhood(): BelongsTo
    {
        return $this->belongsTo(Neighborhood::class, 'neighborhood_id');
    }
    public function entityType(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'entity_type_type', 'entity_type_id');
    }
    public function deliveries(): HasMany
    {
        return $this->hasMany(Deliveries::class, 'address_id');
    }
}
