<?php

namespace Modules\Company\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WeeklyHoliday extends Model
{
    use HasFactory;

    protected $fillable = [
        'dates',
        'company_id',
        'added_by'
    ];
    
    protected static function newFactory()
    {
        return \Modules\Company\Database\factories\WeeklyHolidayFactory::new();
    }
}
