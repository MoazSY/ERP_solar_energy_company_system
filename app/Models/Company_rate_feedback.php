<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Company_rate_feedback extends Model
{
    protected $table = 'company_rate_feedbacks';

    protected $fillable = [
        'customer_id',
        'company_id',
        'rate',
        'feedback',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Solar_company::class, 'company_id');
    }
}
