<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
// use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Company_agency_employee extends Model
{
    use HasApiTokens,Notifiable,HasFactory;
    protected $table = 'company_agency_employees';

    protected $fillable = [
        'employee_id',
        'entity_type_type',
        'entity_type_id',
        'role',
        'salary_type',
        'currency',
        'work_type',
        'payment_method',
        'payment_frequency',
        'salary_rate',
        'salary_amount',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
    public function entityType(): MorphTo
    {
        return $this->morphTo(null, 'entity_type_type', 'entity_type_id');
    }
    public function orderLists(): HasMany
    {
        return $this->hasMany(Order_list::class, 'inventory_manager_id');
    }
    public function deliveries(): HasMany
    {
        return $this->hasMany(Deliveries::class, 'driver_id');
    }
}
