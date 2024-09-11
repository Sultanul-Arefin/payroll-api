<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DemoPayslip extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'country_name',
        'user_name',
        'file_name'
    ];
}
