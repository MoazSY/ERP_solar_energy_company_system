<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
// use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Employee extends Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory;

    protected $table = 'employees';

    protected $fillable = [
        'first_name',
        'last_name',
        'date_of_birth',
        'email',
        'password',
        'phoneNumber',
        'account_number',
        'syriatel_cash_phone',
        'image',
        'identification_image',
        'about_him',
        'is_active',
    ];

    public function refreshTokens()
    {
        return $this->morphMany(Refresh_token::class, 'user_table');
    }

    public function projectTasks(): HasMany
    {
        return $this->hasMany(Project_task::class, 'employee_id');
    }

    public function companyAgencyEmployees(): HasMany
    {
        return $this->hasMany(Company_agency_employee::class, 'employee_id');
    }

    public function inputOutputRequests(): HasMany
    {
        return $this->hasMany(Input_output_request::class, 'inventory_manager_id');
    }

    public function consumables(): HasMany
    {
        return $this->hasMany(Consumables::class, 'technician_id');
    }

    public function productTechicians(): HasMany
    {
        return $this->hasMany(Product_techicians::class, 'technician_id');
    }

    public function productTechiciansByinventoryManager(): HasMany
    {
        return $this->hasMany(Product_techicians::class, 'inventory_manager_id');
    }

    public function taskAssistants(): HasMany
    {
        return $this->hasMany(Task_assistants::class, 'employee_id');
    }

    public function paymentsMade(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function paymentsReceived(): MorphMany
    {
        return $this->morphMany(Payment::class, 'target_table');
    }
}
