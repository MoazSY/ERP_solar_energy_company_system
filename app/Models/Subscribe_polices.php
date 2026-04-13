<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Model;

class Subscribe_polices extends Model
{
    protected $table = 'subscribe_polices';

    protected $fillable = [
        'admin_id',
        'name',
        'description',
        'apply_to',
        'subscription_fee',
        'currency',
        'duration_value',
        'duration_type',
        'is_active',
        'is_trial_granted',
        // 'trial_duration_value',
        // 'trial_duration_type',
        // 'priority',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(System_admin::class, 'admin_id');
    }

    public function customSubscribes(): HasMany
    {
        return $this->hasMany(Custom_subscribe::class, 'subscribe_policy_id');
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payment_object_table');
    }
}
