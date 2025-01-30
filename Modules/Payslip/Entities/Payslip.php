<?php

namespace Modules\Payslip\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Company\Entities\Company;

class Payslip extends Model
{
    use HasFactory, HasUuids;

    public const EMAIL_SENT = 1;

    public const EMAIL_NOT_SENT = 0;

    public const NOTIFICATION_SENT = 1;

    public const NOTIFICATION_NOT_SENT = 0;

    public const PAY_FREQUENCY_MONTHLY = 1;
    public const PAY_FREQUENCY_HOURLY = 2;

    protected $fillable = [
        'employee_id',
        'company_id',
        'month',
        // 'amount',
        'email_flag',
        'notification_flag',
        'pay_frequency',
        'first_date',
        'last_date',
        'payment_date',
        'hours_worked',
        'wages',
        'additional_pay',
        'leave_deduction',
        'total_pay_value',
        'taxable_allowance',
        'gross_pay_before_tax',
        'tax_value',
        'post_tax_value',
        'gross_pay_after_tax',
        'non_taxable_allowance',
        'pay_due_before_deduction',
        'company_contribution_value',
        'employee_contribution_value',
        'other_company_deduction',
        'other_company_contribution',
        'net_pay',
        'total_employee_deduction',
        'company_contribution'
    ];

    /**
     * changes while using uuid
     */
    // protected $keyType = 'string';
    // public $incrementing = false;

    // protected static function boot()
    // {
    //     parent::boot();

    //     static::creating(function($model){
    //         $model->id = (string) \Illuminate\Support\Str::uuid();
    //     });
    // }

    /**
     * other methods
     */

     public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id', 'id');
    }

    public function payslip_details(): HasMany
    {
        return $this->hasMany(PayslipDetail::class, 'payslip_id', 'id');
    }
}
