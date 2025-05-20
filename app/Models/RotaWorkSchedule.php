<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RotaWorkSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'day',
        'work_status'
    ];
}
