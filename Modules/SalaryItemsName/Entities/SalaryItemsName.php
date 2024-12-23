<?php

namespace Modules\SalaryItemsName\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\EmployeeSalaryItems\Entities\EmployeeSalaryItem;
use Modules\SalaryItemsCategory\Entities\SalaryItemsCategory;

class SalaryItemsName extends Model
{
    use HasFactory;

    protected $fillable = [
        'salary_items_category_id',
        'name',
        'company_id',
        'is_threshold',
        'country_id'
    ];

    public const INCOME_TAX_STRAIGHT = 1;
    public const INCOME_TAX_THRESHOLD = 2;

    protected static function newFactory()
    {
        return \Modules\SalaryItemsName\Database\factories\SalaryItemsNameFactory::new();
    }

    public function leave_salary_items(): HasOne
    {
        return $this->hasOne(LeaveSalaryItems::class, 'salary_items_id', 'id');
    }

    public function salaryItemsCategory(): BelongsTo
    {
        return $this->belongsTo(SalaryItemsCategory::class, 'salary_items_category_id', 'id');
    }

    public function employeeSalaryItem(): HasMany
    {
        return $this->hasMany(EmployeeSalaryItem::class, 'salary_item_id', 'id');
    }
}
