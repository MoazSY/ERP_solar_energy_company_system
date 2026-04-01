<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Order_list extends Model
{
    protected $table = 'order_lists';

    protected $fillable = [
        'request_entity_type_type',
        'request_entity_type_id',
        'orderable_entity_type_type',
        'orderable_entity_type_id',
        'customer_first_name',
        'customer_last_name',
        'status',
        'sub_total_amount',
        'total_discount_amount',
        'total_amount',
        'inventory_manager_id',
        'identical_state',
        'request_datetime',
        'discharge_datetime',
        'recieve_datetime',
    ];

    public function inventoryManager(): BelongsTo
    {
        return $this->belongsTo(Company_agency_employee::class, 'inventory_manager_id');
    }
    public function requestEntityType(): MorphTo
    {
        return $this->morphTo(null, 'request_entity_type_type', 'request_entity_type_id');
    }
    public function orderableEntityType(): MorphTo
    {
        return $this->morphTo(null, 'orderable_entity_type_type', 'orderable_entity_type_id');
    }
    public function inputOutputRequests(): HasMany
    {
        return $this->hasMany(Input_output_request::class, 'order_id');
    }
    public function deliveries(): HasMany
    {
        return $this->hasMany(Deliveries::class, 'order_list_id');
    }
    public function purchaseInvoices(): HasMany
    {
        return $this->hasMany(Purchase_invoice::class, 'order_list_id');
    }
}
