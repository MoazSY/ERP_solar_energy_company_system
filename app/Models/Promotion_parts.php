<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Promotion_parts extends Model
{
    protected $table = 'promotion_parts';

    protected $fillable = [
        'promotion_plan_id',
        'promotion_id',
        'promotable_type',
        'promotable_id',
        'start_period',
        'end_period',
    ];

    public function promotionPlan(): BelongsTo
    {
        return $this->belongsTo(Promotion_plan::class, 'promotion_plan_id');
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class, 'promotion_id');
    }

    public function promotable(): MorphTo
    {
        return $this->morphTo();
    }
}
