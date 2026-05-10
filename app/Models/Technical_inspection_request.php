<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Technical_inspection_request extends Model
{
    protected $table = 'technical_inspection_requests';

    protected $fillable = [
        'company_id',
        'customer_id',
        'customer_name',
        'customer_phone',
        'customer_address',
        'inspection_status',
        'priority',
        'issue_description',
        'image_state',
        'inspection_price',
        'response_date',
        'expected_date',
        'payment_method',
        'currency',
    ];

    protected $casts = [
        'response_date' => 'datetime',
        'expected_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Solar_company::class, 'company_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
