<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project_warranties extends Model
{
    protected $table = 'project_warranties';

    protected $fillable = [
        'invoice_id',
        'customer_id',
        'customer_name',
        'company_id',
        'provider_name',
        'warranty_status',
        'warranty_number',
        'project_serial_number',
        'warranty_terms',
        'start_date',
        'end_date',
        'installation_warranty_years',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Purchase_invoice::class, 'invoice_id');
    }
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
    public function company(): BelongsTo
    {
        return $this->belongsTo(Solar_company::class, 'company_id');
    }
    public function componentWarranties(): HasMany
    {
        return $this->hasMany(Component_warranties::class, 'project_warranty_id');
    }
}
