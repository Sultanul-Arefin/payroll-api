<?php

namespace Modules\LeaveManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserLeave extends Model
{
    use HasFactory;

    protected $fillable = [];

    public const APPROVED = 1;
    public const PENDING = 2;
    public const DENIED = 3;
}
