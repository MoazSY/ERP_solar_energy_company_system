<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product_techicians extends Model
{
    protected $table = 'product_techicians';

    protected $fillable = [
        'technician_id',
        'inventory_manager_id',
        'task_id',
        'item_id',
    ];

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'technician_id');
    }
    public function inventoryManager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'inventory_manager_id');
    }
    public function task(): BelongsTo
    {
        return $this->belongsTo(Project_task::class, 'task_id');
    }
    public function item(): BelongsTo
    {
        return $this->belongsTo(Items::class, 'item_id');
    }
}
