<?php

namespace Modules\ProjectManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectAssociatedColumn extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'project_column_id',
        'created_by'
    ];
    
    protected static function newFactory()
    {
        return \Modules\ProjectManagement\Database\factories\ProjectAssociatedColumnFactory::new();
    }

    /**
     * @return belongsTo
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    /**
     * @return belongsTo
     */
    public function project_column(): BelongsTo
    {
        return $this->belongsTo(ProjectColumn::class, 'project_column_id', 'id');
    }

    /**
     * @return hasMany
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'project_associated_column_id', 'id');
    }
}
