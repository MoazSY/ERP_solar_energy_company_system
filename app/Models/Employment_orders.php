<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employment_orders extends Model
{
    protected $table='employment_orders';
    protected $fillable = [
        'employee_id',
        'entity_type_id',
        'entity_type_type',
        'job_title',
        'status',
        'reject_cause'
    ];
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    public function entity_type()
    {
        return $this->morphTo(null,'entity_type_type','entity_type_id');
    }
}
