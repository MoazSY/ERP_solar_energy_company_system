<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Project_task extends Model
{
    protected $table = 'project_tasks';

    protected $fillable = [
        'company_id',
        'employee_id',
        'taskable_type',
        'taskable_id',
        'task_accepted',
        'rejected_reason',
        'accepted_at',
        'rejected_at',
        'task_type',
        'delivery_id',
        'task_fee',
        'manager_payed',
        'manager_payed_at',
        'task_status',
        'task_images',
        'client_recieve_task',
        'employee_notes',
        'manager_notes',
        'num_assistants',
        'assistant_names',
        'client_additional_cost_amount',
        'client_additional_entitlement_amount',
        'payment_status',
        'payment_method',
        'payment_received',
        'sheduled_at',
        'started_at',
        'completed_at',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Solar_company::class, 'company_id');
    }
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Deliveries::class, 'delivery_id');
    }
    public function taskable(): MorphTo
    {
        return $this->morphTo(null, 'taskable_type', 'taskable_id');
    }
    public function consumables(): HasMany
    {
        return $this->hasMany(Consumables::class, 'task_id');
    }
    public function companyProtofolios(): HasMany
    {
        return $this->hasMany(Company_protofolio::class, 'project_task_id');
    }
    public function customerRateFeedbacks(): HasMany
    {
        return $this->hasMany(Customer_rate_feedback::class, 'task_id');
    }
    public function productTechicians(): HasMany
    {
        return $this->hasMany(Product_techicians::class, 'task_id');
    }
    public function taskAssistants(): HasMany
    {
        return $this->hasMany(Task_assistants::class, 'task_id');
    }
}
