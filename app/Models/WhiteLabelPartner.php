<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhiteLabelPartner extends Model
{
    use HasFactory;

    public const USER_ACTIVE = 1;
    public const USER_DISABLE = 0;
    public const USER_PENDING = 2;
}
