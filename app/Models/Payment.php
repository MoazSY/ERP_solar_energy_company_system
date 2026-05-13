<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    protected $table = 'payments';

    protected $fillable = [
        'payable_type',
        'payable_id',
        'target_table_type',
        'target_table_id',
        'payment_object_table_type',
        'payment_object_table_id',
        'payment_object_type_name',
        'amount',
        'currency',
        'paid_at',
        'status',
        // 'payment_method',
        // 'transaction_id',
        're_subscribed',
    ];

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }
    public function transaction():HasOne
    {
        return $this->hasOne(Payment_transactions::class, 'payment_id');
    }
    public function targetTable(): MorphTo
    {
        return $this->morphTo(null, 'target_table_type', 'target_table_id');
    }

    public function payment_object_table(): MorphTo
    {
        return $this->morphTo(null, 'payment_object_table_type', 'payment_object_table_id');
    }
}
