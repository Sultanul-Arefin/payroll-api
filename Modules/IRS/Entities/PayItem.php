<?php

namespace Modules\IRS\Entities;

use Illuminate\Database\Eloquent\Model;

class PayItem extends Model
{
    protected $table = 'pay_items'; 

    protected $fillable = [
        'payslip_id',
        'name',
        'amount',
        'category_id', 
    ];
}