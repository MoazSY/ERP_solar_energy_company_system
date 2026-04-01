<?php

namespace App\Models;

use Illuminate\Container\Attributes\Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Solar_company_manager extends Authenticatable
{
    use HasApiTokens,Notifiable,HasFactory;
    protected $table = 'solar_company_managers';

    protected $fillable = [
        'first_name',
        'last_name',
        'date_of_birth',
        'email',
        'password',
        'phoneNumber',
        'account_number',
        'image',
        'identification_image',
        'about_him',
        'Activate_Account'
    ];

    public function refreshTokens()
    {
        return $this->morphMany(Refresh_token::class, 'user_table');
    }
    public function solarCompanies(): HasMany
    {
        return $this->hasMany(Solar_company::class, 'solar_company_manager_id');
    }
}
