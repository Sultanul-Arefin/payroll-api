<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffTaskMainPoint extends Model
{
    use HasFactory;

    public const COMPLETE = 1;
    public const INCOMPLETE = 2;
    public const PENDING = 3;

    public const SINGED_OFF = 1;
    public const NOT_SINGED_OFF = 2;
}
