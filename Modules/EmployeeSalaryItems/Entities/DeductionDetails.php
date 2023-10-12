<?php

namespace Modules\EmployeeSalaryItems\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeductionDetails extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_salary_item_id',
        'employee_amount',
        'government_or_company_amount'
    ];
    
    protected static function newFactory()
    {
        return \Modules\EmployeeSalaryItems\Database\factories\DeductionDetailsFactory::new();
    }

    /**
     * @return BelongsTo
     */
    function employee_salary_item(): BelongsTo {
        return $this->belongsTo(EmployeeSalaryItem::class, 'employee_salary_item_id', 'id');
    }
}
