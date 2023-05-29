<?php

namespace Modules\EmployeeSalaryItems\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DeductionDetails extends Model
{
    use HasFactory;

    protected $fillable = [];
    
    protected static function newFactory()
    {
        return \Modules\EmployeeSalaryItems\Database\factories\DeductionDetailsFactory::new();
    }
}
