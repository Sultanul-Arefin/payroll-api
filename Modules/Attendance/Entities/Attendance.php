<?php

namespace Modules\Attendance\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'dates',
        'user_id',
        'status'
    ];
    
    protected static function newFactory()
    {
        return \Modules\Attendance\Database\factories\AttendanceFactory::new();
    }

    public const ABSENT = 0;
    public const PRESENT = 1;
    public const PENDING = 2;
}
