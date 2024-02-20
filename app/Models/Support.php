<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Support extends Model
{
    use HasFactory;

    public const CLOSED = 0;
    public const ACTIVE = 1;
    public const PENDING = 2;
}
