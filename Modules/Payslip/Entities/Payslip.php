<?php

namespace Modules\Payslip\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payslip extends Model
{
    use HasFactory;

    public const EMAIL_SENT = 1;
    public const EMAIL_NOT_SENT = 0;

    public const NOTIFICATION_SENT = 1;
    public const NOTIFICATION_NOT_SENT = 0;

    protected $fillable = [
        'employee_id',
        'company_id',
        'month',
        // 'amount',
        'email_flag',
        'notification_flag',
        'first_date',
        'last_date',
        'payment_date',
        'hours_worked',
        'wages',
        'leave_deduction',
        'total_pay_value',
        'taxable_allowance',
        'gross_pay_before_tax',
        'tax_value',
        'post_tax_value',
        'non_taxable_allowance',
        'pay_deduction',
        'net_pay'
    ];
    
    /**
     * changes while using uuid
     */
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function($model){
            $model->id = (string) \Illuminate\Support\Str::uuid();
        });
    }

    /**
     * other methods
     */
    
    /**
     * @return belongsTo
     */
    function employee(): BelongsTo {
        return $this->belongsTo(User::class, 'employee_id', 'id');
    }
}
