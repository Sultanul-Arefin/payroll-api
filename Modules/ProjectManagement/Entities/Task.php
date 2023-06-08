<?php

namespace Modules\ProjectManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_associated_column_id',
        'project_id',
        'task_title',
        'task_description',
        'estimation_hour',
        'start_date_time',
        'end_date_time',
        'created_by'
    ];

    public const COMPLETED = 1;
    public const NOT_COMPLETED = 0;

    /**
     * @return belongsTo
     */
    public function project_associated_column(): BelongsTo
    {
        return $this->belongsTo(ProjectAssociatedColumn::class, 'project_associated_column_id', 'id');
    }
}
