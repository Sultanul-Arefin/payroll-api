<?php

namespace Modules\Payslip\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\SalaryItemsName\Entities\SalaryItemsName;

class PayslipDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'payslip_id',
        'salary_item_id',
        'amount',
        'base_amount_or_hours'
    ];

    /**
     * @return BelongsTo
     */
    public function salary_item(): BelongsTo
    {
        return $this->belongsTo(SalaryItemsName::class, 'salary_item_id', 'id');
    }
}
