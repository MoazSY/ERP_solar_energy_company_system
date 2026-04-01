<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Custom_commision extends Model
{
    protected $table = 'custom_commisions';

    protected $fillable = [
        'commision_police_id',
        'commisionable_type',
        'commisionable_id',
    ];

    public function commisionPolice(): BelongsTo
    {
        return $this->belongsTo(Commision_polices::class, 'commision_police_id');
    }

    public function commisionable(): MorphTo
    {
        return $this->morphTo();
    }
}
