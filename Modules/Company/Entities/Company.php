<?php

namespace Modules\Company\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Company extends Model
{
    use HasFactory;

    // protected static function newFactory()
    // {
    //     return \Modules\Company\Database\factories\CompanyFactory::new();
    // }

    protected $fillable = [
        'company_name',
        'company_address',
        'company_email',
    ];
}
