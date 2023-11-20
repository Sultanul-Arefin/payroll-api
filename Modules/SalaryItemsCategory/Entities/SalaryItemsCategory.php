<?php

namespace Modules\SalaryItemsCategory\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\SalaryItemsName\Entities\SalaryItemsName;

class SalaryItemsCategory extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected static function newFactory()
    {
        return \Modules\SalaryItemsCategory\Database\factories\SalaryItemsCategoryFactory::new();
    }

    public function salaryItemsName(): HasMany
    {
        return $this->hasMany(SalaryItemsName::class, 'salary_items_category_id', 'id');
    }
}
