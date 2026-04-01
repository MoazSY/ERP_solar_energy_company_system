<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Component_warranties extends Model
{
    protected $table = 'component_warranties';

    protected $fillable = [
        'project_warranty_id',
        'item_id',
        'company_id',
        'customer_id',
        'customer_name',
        'provider_name',
        'component_type',
        'warranty_years',
        'warranty_terms',
        'product_name',
        'product_serial_number',
        'warranty_status',
        'warranty_source',
        'start_date',
        'end_date',
    ];

    public function projectWarranty(): BelongsTo
    {
        return $this->belongsTo(Project_warranties::class, 'project_warranty_id');
    }
    public function item(): BelongsTo
    {
        return $this->belongsTo(Items::class, 'item_id');
    }
    public function company(): BelongsTo
    {
        return $this->belongsTo(Solar_company::class, 'company_id');
    }
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
