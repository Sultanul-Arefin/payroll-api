<?php

namespace Modules\Payslip\Entities;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PayslipDetailsForDeduction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'payslip_id',
        'employee_amount',
        'government_or_company_amount'
    ];
}
