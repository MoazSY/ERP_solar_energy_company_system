<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Metainence_request extends Model
{
    protected $table = 'metainence_requests';

    protected $fillable = [
        'company_id',
        'customer_id',
        'customer_name',
        'customer_phone',
        'metainence_type',
        'issue_category',
        'priority',
        'issue_description',
        'manager_approval',
        'manager_notes',
        'metainence_status',
        'metainence_scheduled_at',
        'system_sn',
        'warranty_number',
        'image_state',
        'estimated_cost',
        'problem_name',
        'problem_cause',
        'is_paid',
        'payment_method',
        'currency',
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
