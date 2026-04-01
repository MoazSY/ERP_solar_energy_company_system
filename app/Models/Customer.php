<?php

namespace App\Models;

use Illuminate\Container\Attributes\Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasApiTokens,HasFactory,Notifiable;
    protected $table = 'customers';

    protected $fillable = [
        'first_name',
        'last_name',
        'date_of_birth',
        'email',
        'password',
        'phone_number',
        'account_number',
        'image',
        'about_him',
    ];
    
    public function refreshTokens()
    {
        return $this->morphMany(Refresh_token::class, 'user_table');
    }
    public function componentWarranties(): HasMany
    {
        return $this->hasMany(Component_warranties::class, 'customer_id');
    }
    public function offers(): HasMany
    {
        return $this->hasMany(Offers::class, 'customer_id');
    }
    public function subscribeOffers(): HasMany
    {
        return $this->hasMany(Subscribe_offer::class, 'customer_id');
    }
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'customer_id');
    }
    public function customerRateFeedbacks(): HasMany
    {
        return $this->hasMany(Customer_rate_feedback::class, 'customer_id');
    }
    public function projectWarranties(): HasMany
    {
        return $this->hasMany(Project_warranties::class, 'customer_id');
    }
    public function requestSolarSystems(): HasMany
    {
        return $this->hasMany(Request_solar_system::class, 'customer_id');
    }
    public function metainenceRequests(): HasMany
    {
        return $this->hasMany(Metainence_request::class, 'customer_id');
    }
}
