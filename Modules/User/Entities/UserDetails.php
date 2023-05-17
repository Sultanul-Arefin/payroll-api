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
        'user_address',
        'user_phone',
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