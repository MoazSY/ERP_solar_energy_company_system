<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Order_list extends Model
{
    protected $table = 'order_lists';

    protected $fillable = [
        'request_entity_type',
        'request_entity_id',
        'orderable_entity_type',
        'orderable_entity_id',
        'customer_first_name',
        'customer_last_name',
        'status',
        'sub_total_amount',
        'total_discount_amount',
        'total_amount',
        'with_delivery',
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
    public function request_entity(): MorphTo
    {
        return $this->morphTo(null, 'request_entity_type', 'request_entity_id');
    }
    public function orderableEntityType(): MorphTo
    {
        return $this->morphTo(null, 'orderable_entity_type', 'orderable_entity_id');
    }
    public function inputOutputRequests(): HasMany
    {
        return $this->hasMany(Input_output_request::class, 'order_id');
    }
    public function deliveries(): HasMany
    {
        return $this->hasMany(Deliveries::class, 'order_list_id');
    }
    public function purchaseInvoices(): HasOne
    {
        return $this->hasOne(Purchase_invoice::class, 'order_list_id');
    }
    public function Items():MorphMany
    {
        return $this->morphMany(Items::class, 'itemable');
    }
    public function Payment(): MorphOne{
        return $this->morphOne(Payment::class,'payment_object_table');
    }
}
