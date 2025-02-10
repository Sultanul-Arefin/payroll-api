<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bonus extends Model
{
    use HasFactory;

    public const BONUS_HOURLY = 0;
    public const BONUS_SALARY_BASIC = 1;
    public const BONUS_DIRECT_AMOUNT = 2;
    public const OVERTIME = 3;
    public const DOUBLE_OVERTIME = 4;
    public const RECUPERATED = 5;

    protected $fillable = [
        'employee_id',
        'type',
        'given_amount',
        'generalize_amount',
        'date'
    ];
}
