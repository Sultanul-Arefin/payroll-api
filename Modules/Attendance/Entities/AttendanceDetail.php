<?php

namespace Modules\Attendance\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
}
