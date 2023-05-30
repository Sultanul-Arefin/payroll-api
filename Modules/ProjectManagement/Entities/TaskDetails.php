<?php

namespace Modules\ProjectManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaskDetails extends Model
{
    use HasFactory;

    protected $fillable = [];
    
    protected static function newFactory()
    {
        return \Modules\ProjectManagement\Database\factories\TaskDetailsFactory::new();
    }
}
