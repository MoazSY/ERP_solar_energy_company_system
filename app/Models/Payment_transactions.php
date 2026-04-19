<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment_transactions extends Model
{
    protected $table = 'payment_transactions';

    protected $fillable = [
        'payment_id',
        'gateway',
        'external_id',
        'payment_url',
        'status',
        'response',
    ];

    protected $casts = [
        'response' => 'array',
    ];
}
