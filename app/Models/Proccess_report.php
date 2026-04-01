<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Proccess_report extends Model
{
    protected $table = 'proccess_reports';

    protected $fillable = [
        'report_id',
        'admin_id',
        'proccess_method',
        'block_type',
        'block_duaration_value',
        'compensation_amount',
        'fine_amount',
        'notes',
        'proccess_datetime',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'report_id');
    }
    public function admin(): BelongsTo
    {
        return $this->belongsTo(System_admin::class, 'admin_id');
    }
}
