<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class System_admin extends Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory;

    protected $table = 'system_admins';

    protected $fillable = [
        'first_name',
        'last_name',
        'date_of_birth',
        'email',
        'password',
        'phoneNumber',
        'account_number',
        'image',
        'about_him',
    ];

    public function refreshTokens()
    {
        return $this->morphMany(Refresh_token::class, 'user_table');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'admin_id');
    }

    public function proccessReports(): HasMany
    {
        return $this->hasMany(Proccess_report::class, 'admin_id');
    }

    public function commisionPolices(): HasMany
    {
        return $this->hasMany(Commision_polices::class, 'admin_id');
    }

    public function commisionCharges(): HasMany
    {
        return $this->hasMany(Commision_charges::class, 'admin_id');
    }

    public function subscribePolices(): HasMany
    {
        return $this->hasMany(Subscribe_polices::class, 'admin_id');
    }

    public function promotionPlans(): HasMany
    {
        return $this->hasMany(Promotion_plan::class, 'admin_id');
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class, 'admin_id');
    }

    public function companyAgencyRigesters(): HasMany
    {
        return $this->hasMany(Company_Agency_rigester::class, 'admin_id');
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
