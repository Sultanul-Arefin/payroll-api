<?php

namespace Modules\ProjectManagement\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;


class Task extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'project_associated_column_id',
        'project_id',
        'task_title',
        'task_description',
        'estimation_hour',
        'start_date_time',
        'end_date_time',
        'status',
        'percentage',
        'created_by',
    ];

    public const BACKLOG = 0;
    public const COMPLETED = 1;
    public const IN_PROGRESS = 2;
    public const CANCELLED = 3;

    public function project_associated_column(): BelongsTo
    {
        return $this->belongsTo(ProjectAssociatedColumn::class, 'project_associated_column_id', 'id');
    }

    public function associated_users(): HasMany
    {
        return $this->hasMany(TaskAssociatedEmployee::class, 'task_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    /**
     * @return HasMany
     */
    function comments(): HasMany {
        return $this->hasMany(TaskComment::class, 'task_id', 'id');
    }
    public function taskFiles()
    {
        return $this->hasMany(TaskFile::class,'task_id', 'id');
    }
}
