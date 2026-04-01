<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promotion_plan extends Model
{
    protected $table = 'promotion_plans';

    protected $fillable = [
        'admin_id',
        'plan_name',
        'plan_description',
        'plan_price',
        'currency',
        'duration_days',
        'priority_level',
        'priority_value',
        'allows_banar',
        'is_active',
        'start_date',
        'end_date',
        'max_promotions',
        'max_daily_promotion_period',
        'promotion_part',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(System_admin::class, 'admin_id');
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class, 'promotion_plan_id');
    }

    public function promotionGovernorates(): HasMany
    {
        return $this->hasMany(Promotion_governorates::class, 'promotion_plan_id');
    }

    public function promotionParts(): HasMany
    {
        return $this->hasMany(Promotion_parts::class, 'promotion_plan_id');
    }
}
