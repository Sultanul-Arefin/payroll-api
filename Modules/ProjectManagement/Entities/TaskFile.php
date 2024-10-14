<?php

namespace Modules\ProjectManagement\Entities;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskFile extends Model
{
    use HasFactory;

    protected $fillable = ['task_id', 'files', 'uploaded_by'];

    protected function fileName(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => ($value ? asset('storage').'/'.$value : null)
        );
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
