<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer_rate_feedback extends Model
{
    protected $table = 'customer_rate_feedbacks';

    protected $fillable = [
        'customer_id',
        'task_id',
        'rate',
        'feedback',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
    public function task(): BelongsTo
    {
        return $this->belongsTo(Project_task::class, 'task_id');
    }
}
