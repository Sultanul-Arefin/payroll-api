<?php

namespace Modules\Attendance\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'in_time',
        'out_time'
    ];
    
    protected static function newFactory()
    {
        return \Modules\Attendance\Database\factories\AttendanceDetailFactory::new();
    }

    /**
     * @return BelongsTo
     */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(AttendanceDetail::class, 'attendance_id', 'id');
    }
}
