<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offers::class, 'offer_id');
    }
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
