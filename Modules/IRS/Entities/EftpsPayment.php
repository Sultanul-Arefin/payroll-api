<?php

namespace Modules\IRS\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\IRS\Database\Factories\EftpsPaymentFactory;

class EftpsPayment extends Model
{
    use HasFactory;

    protected $table = 'eftps_payments';   
    
    protected $fillable = [
        'company_id',
        'tax_type',
        'amount',
        'period',
        'submission_id',
        'record_id',
        'payment_date',
        'status',
    ];
     
}
