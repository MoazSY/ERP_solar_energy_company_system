<?php

namespace App\Models;

use Illuminate\Container\Attributes\Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;
use App\Models\Customer_electrical_device_characteristic;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'customers';

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
        'about_him',
    ];

    public function refreshTokens()
    {
        return $this->morphMany(Refresh_token::class, 'user_table');
    }
    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'entity_type');
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

    public function electricalDeviceCharacteristics(): HasMany
    {
        return $this->hasMany(Customer_electrical_device_characteristic::class, 'customer_id');
    }

    public function metainenceRequests(): HasMany
    {
        return $this->hasMany(Metainence_request::class, 'customer_id');
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
