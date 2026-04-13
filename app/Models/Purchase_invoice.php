<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Model;

class Purchase_invoice extends Model
{
    protected $table = 'purchase_invoices';

    protected $fillable = [
        'seller_entity_type_type',
        'seller_entity_type_id',
        'buyer_entity_type_type',
        'buyer_entity_type_id',
        'buyer_name',
        'buyer_phone',
        'order_list_id',
        'object_entity_type_type',
        'object_entity_type_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'currency',
        'delivery_fee',
        'installation_fee',
        'subtotal',
        'total_discount',
        'total_amount',
        'payment_status',
        'consumables_id',
        'consumables_amount',
        'payment_method',
        'payment_conumables_method',
        'net_profit',
    ];

    public function orderList(): BelongsTo
    {
        return $this->belongsTo(Order_list::class, 'order_list_id');
    }

    public function consumables(): BelongsTo
    {
        return $this->belongsTo(Consumables::class, 'consumables_id');
    }

    public function sellerEntityType(): MorphTo
    {
        return $this->morphTo(null, 'seller_entity_type_type', 'seller_entity_type_id');
    }

    public function buyerEntityType(): MorphTo
    {
        return $this->morphTo(null, 'buyer_entity_type_type', 'buyer_entity_type_id');
    }

    public function objectEntityType(): MorphTo
    {
        return $this->morphTo(null, 'object_entity_type_type', 'object_entity_type_id');
    }

    public function conflictInvoices(): HasMany
    {
        return $this->hasMany(Conflict_invoice::class, 'invoice_id');
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payment_object_table');
    }
}
