<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Commision_charges extends Model
{
    protected $table = 'commision_charges';

    protected $fillable = [
        'admin_id',
        'commision_police_id',
        'target_table_type',
        'target_table_id',
        'invoice_id',
        'sales_amount',
        'commision_amount',
        'paid_at',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(System_admin::class, 'admin_id');
    }

    public function commisionPolice(): BelongsTo
    {
        return $this->belongsTo(Commision_polices::class, 'commision_police_id');
    }

    public function targetTable(): MorphTo
    {
        return $this->morphTo(null, 'target_table_type', 'target_table_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Purchase_invoice::class, 'invoice_id');
    }
}
