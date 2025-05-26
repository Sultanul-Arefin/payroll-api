<?php

namespace Modules\User\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDetails extends Model
{
    use HasFactory;

    public const USER_IMAGE_PATH = 'uploads/users/photo/';

    public const UNIVERSAL_PAYSLIP = 1;
    public const FRENCH_PAYSLIP = 2;
    public const UK_PAYSLIP = 3;
    public const USA_PAYSLIP = 4;
    public const INDIAN_PAYSLIP = 5;
    public const AFRICAN_PAYSLIP = 6;
    public const GERMAN_PAYSLIP = 7;
    public const AUSTRALIAN_PAYSLIP = 8;
    public const PORTUGUESE_PAYSLIP = 9;
    public const JAPANESE_PAYSLIP = 10;

    public const WEB_ATTENDANCE = 1;

    public const MACHINE_ATTENDANCE = 2;

    protected $fillable = [
        'user_id',
        'user_area',
        'user_city',
        'zip_code',
        'country_id',
        'user_phone',
        'gender',
        'nid',
        'passport',
        'date_of_birth',
        'joining_date',
        'bank_name',
        'bank_bic_or_swift_code',
        'bank_iban_or_account_no',
        'tin',
        'tax_number',
        'social_security_number',
        'pension_number',
        'visa_number',
        'work_permit_number',
        'national_identity_number',
        'others_number',
        'user_image',
        'payslip_type',
        'attendance_type',
        'state',
        'region',
        'uan_no',
        'pf_no',
        'esi_no',
        'national_insurance_number',
        'ni_category',
        'religion',
        'health_insurance',
        'pension_administrator',
        "pension_pin",
        "tin_number",
        "abn_number"
        
    ];

    protected static function newFactory()
    {
        return \Modules\User\Database\factories\UserDetailsFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function getImageUserAttribute()
    {
        return $this->user_image;
    }

    public function getChangedUserImageAttribute()
    {
        return $this->user_image ? env('APP_URL').'/'.'storage/'.$this->user_image : null;
    }

    protected function fineName(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => strtoupper($value)
        );
    }
}
