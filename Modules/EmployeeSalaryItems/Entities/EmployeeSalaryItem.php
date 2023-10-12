<?php

namespace Modules\EmployeeSalaryItems\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\SalaryItemsName\Entities\SalaryItemsName;

class EmployeeSalaryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'salary_item_id',
        'employee_id',
        'company_id',
        'is_percentage',
        'is_general',
        'amount'
    ];
    
    protected static function newFactory()
    {
        return \Modules\EmployeeSalaryItems\Database\factories\EmployeeSalaryItemFactory::new();
    }

    public const IS_PERCENTAGE = 1;
    public const IS_AMOUNT = 0;

    public const IS_GENERAL = 1;
    public const IS_NOT_GENERAL = 0;

    /**
     * @return belongsTo
     */
    function salaryItemsName(): BelongsTo {
        return $this->belongsTo(SalaryItemsName::class, 'salary_item_id', 'id');
    }

    /**
     * @return belongsTo
     */
    function employee(): BelongsTo {
        return $this->belongsTo(User::class, 'employee_id', 'id');
    }

    /**
     * @return hasMany
     */
    function deduction_details(): HasMany {
        return $this->hasMany(DeductionDetails::class, 'employee_salary_item_id', 'id');
    }
}
