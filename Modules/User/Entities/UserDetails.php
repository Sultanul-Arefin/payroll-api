<?php

namespace Modules\User\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDetails extends Model
{
    use HasFactory;
    public const USER_IMAGE_PATH = 'uploads/users/photo/';
    
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
        'payment_type',
        'bank_name',
        'bank_bic_or_swift_code',
        'bank_iban_or_account_no',
        'tin',
        'user_image',
    ];

    protected static function newFactory()
    {
        return \Modules\User\Database\factories\UserDetailsFactory::new();
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}