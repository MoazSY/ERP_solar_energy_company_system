<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Custom_subscribe extends Model
{
    protected $table = 'custom_subscribe';

    protected $fillable = [
        'subscribe_policy_id',
        'subscribeable_type',
        'subscribeable_id',
        'is_active',
        'entity_subscribe',
    ];

    public function subscribePolicy(): BelongsTo
    {
        return $this->belongsTo(Subscribe_polices::class, 'subscribe_policy_id');
    }

    public function subscribeable(): MorphTo
    {
        return $this->morphTo();
    }
}
