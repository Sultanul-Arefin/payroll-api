<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RotaWorkScheduleCompany extends Model
{
    use HasFactory;

    protected $table = "rota_work_schedules_companies";

    protected $fillable = [
        'company_id',
        'saturday_off',
        'sunday_off',
        'monday_off',
        'tuesday_off',
        'wednesday_off',
        'thursday_off',
        'friday_off',
    ];
}
