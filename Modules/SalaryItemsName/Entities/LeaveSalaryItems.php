<?php

namespace Modules\SalaryItemsName\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeaveSalaryItems extends Model
{
    use HasFactory;

    protected $fillable = [];
    
    protected static function newFactory()
    {
        return \Modules\SalaryItemsName\Database\factories\LeaveSalaryItemsFactory::new();
    }
}
