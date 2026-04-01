<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Commision_polices extends Model
{
    protected $table = 'commision_polices';

    protected $fillable = [
        'admin_id',
        'policy_name',
        'description',
        'target_type',
        'applies_to',
        'commision_type',
        'commision_value',
        'is_active',
        'start_date',
        'end_date',
        'priority',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(System_admin::class, 'admin_id');
    }

    public function customCommisions(): HasMany
    {
        return $this->hasMany(Custom_commision::class, 'commision_police_id');
    }

    public function commisionCharges(): HasMany
    {
        return $this->hasMany(Commision_charges::class, 'commision_police_id');
    }
}
