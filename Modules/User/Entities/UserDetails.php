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
        'user_image',
        'payslip_type',
        'attendance_type',
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
