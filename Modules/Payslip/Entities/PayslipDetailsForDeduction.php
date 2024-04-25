<?php

namespace Modules\Payslip\Entities;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\SalaryItemsName\Entities\SalaryItemsName;

class PayslipDetailsForDeduction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'payslip_id',
        'salary_item_id',
        'employee_amount',
        'government_or_company_amount'
    ];

    public function salary_item_name(): BelongsTo
    {
        return $this->belongsTo(SalaryItemsName::class, 'salary_item_id', 'id');
    }
}
