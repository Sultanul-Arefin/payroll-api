<?php

namespace Modules\ProjectManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaskAssociatedEmployee extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id'
    ];
    
    protected static function newFactory()
    {
        return \Modules\ProjectManagement\Database\factories\TaskAssociatedEmployeeFactory::new();
    }
}
