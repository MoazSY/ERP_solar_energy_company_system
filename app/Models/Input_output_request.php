<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Input_output_request extends Model
{
    protected $table = 'input_output_requests';

    protected $fillable = [
        'company_id',
        'request_type',
        'inventory_manager_id',
        'order_id',
        'status',
        'request_datetime',
        'ready_datetime',
        'notes',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Solar_company::class, 'company_id');
    }
    public function inventoryManager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'inventory_manager_id');
    }
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order_list::class, 'order_id');
    }
}
