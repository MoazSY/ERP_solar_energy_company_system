<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Company_protofolio extends Model
{
    protected $table = 'company_protofolios';

    protected $fillable = [
        'company_id',
        'project_name',
        'title',
        'description',
        'project_status',
        'project_type',
        'location',
        'project_size',
        'system_type',
        'capacity_kw',
        'total_cost',
        'installation_date',
        'project_cover_image',
        'project_images',
        'project_videos',
        'customer_satisfaction',
        'is_featured',
        'project_task_id',
    ];

    protected $casts = [
        'project_images' => 'array',
        'project_videos' => 'array',
        'installation_date' => 'date',
        'is_featured' => 'boolean',
        'customer_satisfaction' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Solar_company::class, 'company_id');
    }

    public function projectTask(): BelongsTo
    {
        return $this->belongsTo(Project_task::class, 'project_task_id');
    }
}
