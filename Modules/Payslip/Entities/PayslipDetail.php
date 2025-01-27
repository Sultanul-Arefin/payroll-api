<?php

namespace Modules\Payslip\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\EmployeeSalaryItems\Entities\EmployeeSalaryItem;
use Modules\SalaryItemsName\Entities\SalaryItemsName;

class PayslipDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'payslip_id',
        'salary_item_id',
        'employee_salary_item_id',
        'amount',
        'base_amount_or_hours',
        'rate'
    ];

    /**
     * @return BelongsTo
     */
    public function salary_item(): BelongsTo
    {
        return $this->belongsTo(SalaryItemsName::class, 'salary_item_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function employee_salary_item(): BelongsTo
    {
        return $this->belongsTo(EmployeeSalaryItem::class, 'employee_salary_item_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class, 'payslip_id', 'id');
    }
}
