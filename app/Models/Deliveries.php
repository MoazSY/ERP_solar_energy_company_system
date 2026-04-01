<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Deliveries extends Model
{
    protected $table = 'deliveries';

    protected $fillable = [
        'deliverable_object_type_type',
        'deliverable_object_type_id',
        'entity_type_type',
        'entity_type_id',
        'order_list_id',
        'delivery_fee',
        'currency',
        'delivery_status',
        'address_id',
        'delivery_address',
        'governorate_id',
        'area_id',
        'contact_name',
        'contact_phone',
        'latitude',
        'longitude',
        'driver_id',
        'scheduled_delivery_datetime',
        'shipped_at',
        'delivered_at',
        'client_recieve_delivery',
        'net_profit',
        'weight_kg',
    ];

    public function orderList(): BelongsTo
    {
        return $this->belongsTo(Order_list::class, 'order_list_id');
    }
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'address_id');
    }
    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorates::class, 'governorate_id');
    }
    public function area(): BelongsTo
    {
        return $this->belongsTo(Areas::class, 'area_id');
    }
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Company_agency_employee::class, 'driver_id');
    }
    public function deliverableObjectType(): MorphTo
    {
        return $this->morphTo(null, 'deliverable_object_type_type', 'deliverable_object_type_id');
    }
    public function entityType(): MorphTo
    {
        return $this->morphTo(null, 'entity_type_type', 'entity_type_id');
    }
    public function projectTasks(): HasMany
    {
        return $this->hasMany(Project_task::class, 'delivery_id');
    }
}
