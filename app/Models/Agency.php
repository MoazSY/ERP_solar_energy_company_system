<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agency extends Model
{
    protected $table = 'agencies';

    protected $fillable = [
        'agency_manager_id',
        'agency_name',
        'agency_logo',
        'commerical_register_number',
        'agency_description',
        'agency_email',
        'agency_phone',
        'tax_number',
        'agency_status',
        'verified_at',
        'working_hours_start',
        'working_hours_end',
    ];

    public function agencyManager(): BelongsTo
    {
        return $this->belongsTo(Agency_manager::class, 'agency_manager_id');
    }
    public function conflictInvoices(): HasMany
    {
        return $this->hasMany(Conflict_invoice::class, 'agency_id');
    }
}
