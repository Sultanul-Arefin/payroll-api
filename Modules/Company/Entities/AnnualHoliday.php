<?php

namespace Modules\Company\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnnualHoliday extends Model
{
    use HasFactory;

    protected $fillable = [
        'dates',
        'holiday_type',
        'company_id',
        'added_by',
    ];

    protected static function newFactory()
    {
        return \Modules\Company\Database\factories\AnnualHolidayFactory::new();
    }
}
