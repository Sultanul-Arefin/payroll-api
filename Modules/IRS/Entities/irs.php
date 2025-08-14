<?php
namespace Modules\Payslip\Entities;

use Illuminate\Database\Eloquent\Model;

class irs  extends Model
{
    protected $table = 'pay_items'; 

    protected $fillable = [
        'payslip_id',
        'name',
        'amount',
        'category_id',
        
    ];
}