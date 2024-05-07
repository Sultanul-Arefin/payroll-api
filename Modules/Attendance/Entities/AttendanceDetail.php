<?php

namespace Modules\Attendance\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceDetail extends Model
{
    public const FROM_OFFICE = 1;
    public const FROM_HOME = 2;

    protected $fillable = [
        'attendance_id',
        'office_type',
        'in_time',
        'out_time',
    ];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class, 'attendance_id', 'id');
    }
}
