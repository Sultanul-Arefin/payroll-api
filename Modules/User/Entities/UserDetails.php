<?php

namespace Modules\User\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserDetails extends Model
{
    use HasFactory;

    protected $fillable = [
        'address',
        'phone',
        'image',
    ];
    
    protected static function newFactory()
    {
        return \Modules\User\Database\factories\UserDetailsFactory::new();
    }
}