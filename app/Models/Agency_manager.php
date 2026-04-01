<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agency_manager extends Model
{
    protected $table = 'agency_managers';

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
