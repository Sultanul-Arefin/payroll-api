<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffTaskEmployee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'staff_task_id', 'employee_id'
    ];

    // Relationships
    public function staffTask()
    {
        return $this->belongsTo(StaffTask::class, 'staff_task_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
