<?php

namespace Modules\ProjectManagement\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class Project extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'project_title',
        'project_description',
        'created_by',
        'company_id',
        'department_id',
        'is_completed',
        'project_manager'
    ];

    protected static function newFactory()
    {
        return \Modules\ProjectManagement\Database\factories\ProjectFactory::new();
    }

    public const CANCELLED = 0;

    public const COMPLETED = 1;

    public const PROCESSING = 2;

    public function project_associated_columns(): HasMany
    {
        return $this->hasMany(ProjectAssociatedColumn::class, 'project_id', 'id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'project_manager', 'id');
    }
}
