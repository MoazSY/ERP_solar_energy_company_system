<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Promotion_governorates extends Model
{
    protected $table = 'promotion_governorates';

    protected $fillable = [
        'promotion_plan_id',
        'promotion_id',
        'governorate_id',
    ];

    public function promotionPlan(): BelongsTo
    {
        return $this->belongsTo(Promotion_plan::class, 'promotion_plan_id');
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class, 'promotion_id');
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorates::class, 'governorate_id');
    }
}
