<?php

namespace App\Models;

use App\Models\Offers;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Model;

class Subscribe_offer extends Model
{
    protected $table = 'subscribe_offers';

    protected $fillable = [
        'offer_id',
        'customer_id',
        'customer_name',
        'customer_phone',
        'system_sn',
        'with_installation',
        'subscription_status',
        'subscription_date',
        'total_amount',
        'additional_cost_amount',
        'additional_entitlement_amount',
        'final_amount',
        'delivery_fee',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offers::class, 'offer_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function Items(): HasManyThrough
    {
        return $this->hasManyThrough(
            Items::class,
            Offers::class,
            'id',
            'itemable_id',
            'offer_id',
            'id'
        )->where('itemable_type', Offers::class);
    }
}
