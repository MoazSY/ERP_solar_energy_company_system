<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Model;

class Offers extends Model
{
    protected $table = 'offers';

    protected $fillable = [
        'company_id',
        'customer_id',
        'customer_name',
        'offer_name',
        'offer_details',
        'system_type',
        'subtotal_amount',
        'discount_amount',
        'discount_type',
        'average_total_amount',
        'currency',
        'validity_days',
        'average_delivery_cost',
        'average_installation_cost',
        'average_metal_installation_cost',
        'status_reply',
        'offer_available',
        'panar_image',
        'video',
        'public_private',
        'offer_date',
        'offer_expired_date',
    ];

    protected $casts = [
        'panar_image' => 'array',
        'offer_date' => 'date',
        'offer_expired_date' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Solar_company::class, 'company_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function subscribeOffers(): HasMany
    {
        return $this->hasMany(Subscribe_offer::class, 'offer_id');
    }

    public function Items(): MorphMany
    {
        return $this->morphMany(Items::class, 'itemable');
    }
}
