<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Agency_rate_feedback extends Model
{
    protected $table = 'agency_rate_feedbacks';

    protected $fillable = [
        'company_id',
        'agency_id',
        'rate',
        'feedback',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Solar_company::class, 'company_id');
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }
}
