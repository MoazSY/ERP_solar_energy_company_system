<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment_transactions extends Model
{
    protected $table='payment_transactions';
    protected $fillable=[
        'payment_id',
        'transaction_id',
        'payment_method',
        'amount',
        'currency',
        'status',
        'paid_at',
    ];  
    
}
