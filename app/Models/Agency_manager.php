<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Agency_manager extends Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory;

    protected $table = 'agency_managers';

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
    ];

    protected $hidden = [
        'password',
    ];

    public function agencies(): HasMany
    {
        return $this->hasMany(Agency::class, 'agency_manager_id');
    }

    public function refreshTokens()
    {
        return $this->morphMany(Refresh_token::class, 'user_table');
    }
}
