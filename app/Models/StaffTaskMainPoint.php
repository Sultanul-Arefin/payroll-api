<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffTaskMainPoint extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'staff_task_id',
        'title',
        'manager_id',
        'assigned_to',
        'given_date',
        'start_date',
        'end_date',
        'progress',
        'documents',
        'estimated_time',
        'signed_off_by_manager',
        'signed_off_time'
    ];

    public const COMPLETE = 1;
    public const INCOMPLETE = 2;
    public const PENDING = 3;

    public const SINGED_OFF = 1;
    public const NOT_SINGED_OFF = 0;

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function assigned()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return BelongsTo
     */
    public function staff_task(): BelongsTo
    {
        return $this->belongsTo(StaffTask::class, 'staff_task_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function sub_points(): HasMany
    {
        return $this->hasMany(StaffTaskSubPoint::class, 'staff_task_main_point_id', 'id');
    }
}
