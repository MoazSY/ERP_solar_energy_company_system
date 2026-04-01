<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Refresh_token extends Model
{
    use Notifiable,HasFactory;
    protected $fillable=[
        'user_table',
        'refresh_token',
        'expires_at'
    ];
    
    public function user_table()
    {
        return $this->morphTo();
    }
}
