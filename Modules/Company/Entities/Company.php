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

    public const ACTIVE = 1;
    public const PENDING = 0;

    protected $fillable = [
        'company_name',
        'company_address',
        'company_email',
        'company_phone',
        'company_logo',
        'company_website',
        'company_registration_no',
        'government_employee_no',
        'fiscal_year_from',
        'fiscal_year_to',
        'bank_name',
        'bank_bic_or_swift_code',
        'bank_iban_or_account_no',
        'contact_person_name',
        'contact_person_email',
        'contact_person_phone',
        'status'
    ];
}
