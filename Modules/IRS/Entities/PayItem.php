<?php

namespace Modules\IRS\Entities;

use Illuminate\Database\Eloquent\Model;

class PayItem extends Model
{
    protected $table = 'pay_items'; // আপনার টেবিল যদি অন্য নামে হয় তাহলে সেটি দিন

    protected $fillable = [
        'payslip_id',
        'name',
        'amount',
        'category_id', // যদি থাকে
    ];
}