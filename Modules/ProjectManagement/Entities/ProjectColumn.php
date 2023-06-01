<?php

namespace Modules\ProjectManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectColumn extends Model
{
    use HasFactory;

    protected $fillable = [
        'column_name',
        'company_id',
        'created_by'
    ];
    
    protected static function newFactory()
    {
        return \Modules\ProjectManagement\Database\factories\ProjectColumnFactory::new();
    }

    /**
     * @return HasMany
     */
    public function project_associated_column(): HasMany
    {
        return $this->hasMany(ProjectAssociatedColumn::class, 'project_column_id', 'id');
    }
}
