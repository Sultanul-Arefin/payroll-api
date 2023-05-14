<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];
    
    protected static function newFactory()
    {
        return \Modules\Employee\Database\factories\EmployeeFactory::new();
    }
}