<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conflict_invoice extends Model
{
    protected $table = 'conflict_invoices';

    protected $fillable = [
        'invoice_id',
        'company_id',
        'agency_id',
        'conflict_type',
        'conflict_amount',
        'conflict_description',
        'image_related',
        'conflict_state',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Purchase_invoice::class, 'invoice_id');
    }
    public function company(): BelongsTo
    {
        return $this->belongsTo(Solar_company::class, 'company_id');
    }
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }
}
