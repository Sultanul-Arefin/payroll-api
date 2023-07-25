<?php

namespace Modules\Payslip\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PayslipDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'payslip_id',
        'salary_item_id',
        'amount'
    ];
}
