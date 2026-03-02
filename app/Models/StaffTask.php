<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffTask extends Model
{
    use HasFactory, SoftDeletes;

    public const ACTIVE = 1;
    public const ARCHIVED = 2;

    protected $fillable = [
        'title', 'created_by', 'manager_id', 'start_date', 'end_date', 'status'
    ];

    // Relationships
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function employees()
    {
        return $this->belongsToMany(User::class, 'staff_task_employees', 'staff_task_id', 'employee_id');
    }

    /**
     * @return HasMany
     */
    public function main_points(): HasMany
    {
        return $this->hasMany(StaffTaskMainPoint::class, 'staff_task_id', 'id');
    }
}
