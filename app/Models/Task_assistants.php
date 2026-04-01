<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task_assistants extends Model
{
    protected $table = 'task_assistants';

    protected $fillable = [
        'employee_id',
        'task_id',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
    public function task(): BelongsTo
    {
        return $this->belongsTo(Project_task::class, 'task_id');
    }
}
