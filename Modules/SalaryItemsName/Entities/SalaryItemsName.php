<?php

namespace Modules\SalaryItemsName\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\EmployeeSalaryItems\Entities\EmployeeSalaryItem;
use Modules\SalaryItemsCategory\Entities\SalaryItemsCategory;

class SalaryItemsName extends Model
{
    use HasFactory;

    protected $fillable = [
        'salary_items_category_id',
        'name',
        'company_id'
    ];
    
    protected static function newFactory()
    {
        return \Modules\SalaryItemsName\Database\factories\SalaryItemsNameFactory::new();
    }

    /**
     * @return HasMany
     */
    function leave_salary_items(): HasMany {
        return $this->hasMany(LeaveSalaryItems::class, 'salary_items_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    function salaryItemsCategory(): BelongsTo {
        return $this->belongsTo(SalaryItemsCategory::class, 'salary_items_category_id', 'id');
    }

    /**
     * @return HasMany
     */
    function employeeSalaryItem(): HasMany {
        return $this->hasMany(EmployeeSalaryItem::class, 'salary_item_id', 'id');
    }
}
