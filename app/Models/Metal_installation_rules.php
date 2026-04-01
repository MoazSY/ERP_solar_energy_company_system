<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Metal_installation_rules extends Model
{
    protected $table = 'metal_installation_rules';

    protected $fillable = [
        'company_id',
        'rule_name',
        'metal_base_type',
        'price_per_panal',
        'front_base_height_m',
        'back_base_height_m',
        'installation_fee',
        'currency',
        'is_active',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Solar_company::class, 'company_id');
    }
}
