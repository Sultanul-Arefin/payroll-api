<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\EmployeeSalaryItems\Entities\EmployeeSalaryItem;

class ThresholdDetails extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_salary_item_id',
        'start_percentage_after',
        'end_percentage_at',
    ];

    public function employee_salary_item(): BelongsTo
    {
        return $this->belongsTo(EmployeeSalaryItem::class, 'employee_salary_item_id', 'id');
    }
}
