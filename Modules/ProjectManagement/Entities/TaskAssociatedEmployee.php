<?php

namespace Modules\ProjectManagement\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskAssociatedEmployee extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
    ];

    protected static function newFactory()
    {
        return \Modules\ProjectManagement\Database\factories\TaskAssociatedEmployeeFactory::new();
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'user_id', 'id');
    }

    public function user_info(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
