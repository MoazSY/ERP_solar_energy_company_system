<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $table = 'promotions';

    protected $fillable = [
        'promotion_plan_id',
        'admin_id',
        'promotable_type',
        'promotable_id',
        'start_date',
        'end_date',
        'status',
        'banar_image',
        'promotion_text',
        'impressions_count',
        'clicks_count',
    ];

    public function promotionPlan(): BelongsTo
    {
        return $this->belongsTo(Promotion_plan::class, 'promotion_plan_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(System_admin::class, 'admin_id');
    }

    public function promotable(): MorphTo
    {
        return $this->morphTo();
    }

    public function promotionGovernorates(): HasMany
    {
        return $this->hasMany(Promotion_governorates::class, 'promotion_id');
    }

    public function promotionParts(): HasMany
    {
        return $this->hasMany(Promotion_parts::class, 'promotion_id');
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payment_object_table');
    }
}
