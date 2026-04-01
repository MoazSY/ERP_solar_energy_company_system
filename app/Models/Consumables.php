<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Consumables extends Model
{
    protected $table = 'consumables';

    protected $fillable = [
        'technician_id',
        'task_id',
        'item_id',
        'quantity_consume',
    ];

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'technician_id');
    }
    public function task(): BelongsTo
    {
        return $this->belongsTo(Project_task::class, 'task_id');
    }
    public function item(): BelongsTo
    {
        return $this->belongsTo(Items::class, 'item_id');
    }
    public function purchaseInvoices(): HasMany
    {
        return $this->hasMany(Purchase_invoice::class, 'consumables_id');
    }
}
