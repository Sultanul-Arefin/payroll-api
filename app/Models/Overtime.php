<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Overtime extends Model
{
    use HasFactory;

    public const OVERTIME = 0;
    public const DOUBLE_OVERTIME = 1;
    public const RECUPERATED = 2;
    public const EARLY_DAY_DEPARTURE = 3;

    protected $fillable = [
        'attendance_id',
        'is_overtime',
        'hour',
        'given_by'
    ];
}
