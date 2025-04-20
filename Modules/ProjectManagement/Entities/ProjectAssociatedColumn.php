<?php

namespace Modules\ProjectManagement\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectAssociatedColumn extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'project_id',
        'project_column_name',
        'created_by',
        'column_position',
    ];

    protected static function newFactory()
    {
        return \Modules\ProjectManagement\Database\factories\ProjectAssociatedColumnFactory::new();
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'project_associated_column_id', 'id');
    }
}
