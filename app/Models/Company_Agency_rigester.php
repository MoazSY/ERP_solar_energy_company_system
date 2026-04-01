<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Company_Agency_rigester extends Model
{
    protected $table = 'company_agency_rigesters';

    protected $fillable = [
        'admin_id',
        'registerable_type',
        'registerable_id',
        'status',
        'rejection_reason',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(System_admin::class, 'admin_id');
    }

    public function registerable(): MorphTo
    {
        return $this->morphTo();
    }
}
