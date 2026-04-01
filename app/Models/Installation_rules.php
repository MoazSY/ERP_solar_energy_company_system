<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Installation_rules extends Model
{
    protected $table = 'installation_rules';

    protected $fillable = [
        'company_id',
        'rule_name',
        'system_type',
        'installation_fee',
        'price_per_kw',
        'price_per_panal',
        'general_terms',
        'currency',
        'is_active',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Solar_company::class, 'company_id');
    }
}
