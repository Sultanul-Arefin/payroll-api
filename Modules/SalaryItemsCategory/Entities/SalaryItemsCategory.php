<?php

namespace Modules\SalaryItemsCategory\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalaryItemsCategory extends Model
{
    use HasFactory;

    protected $fillable = [];
    
    protected static function newFactory()
    {
        return \Modules\SalaryItemsCategory\Database\factories\SalaryItemsCategoryFactory::new();
    }
}
