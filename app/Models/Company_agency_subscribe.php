<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company_agency_subscribe extends Model
{
    protected $table='company_agency_subscribe';
    protected $fillable=[
        'subscribe_policy_id',
        'subscribable_type',
        'subscribable_id',
        'is_active',
        'start_date',
        'end_date',
    ];
    public function subscribePolicy()
    {
        return $this->belongsTo(Subscribe_polices::class, 'subscribe_policy_id');
}
public function subscribable()
    {
        return $this->morphTo();
    }
}