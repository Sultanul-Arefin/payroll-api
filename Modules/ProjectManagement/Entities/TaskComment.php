<?php

namespace Modules\ProjectManagement\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'comments',
        'comment_by',
        'parent_id'
    ];

    protected static function newFactory()
    {
        return \Modules\ProjectManagement\Database\factories\TaskCommentFactory::new();
    }

    /**
     * @return BelongsTo
     */
    function task(): BelongsTo {
        return $this->belongsTo(Task::class, 'task_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    function user(): BelongsTo {
        return $this->belongsTo(User::class, 'comment_by', 'id');
    }
}
