<?php

namespace Modules\LeaveManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserLeave extends Model
{
    use HasFactory;

    public const APPROVED = 1;
    public const PENDING = 2;
    public const DENIED = 3;

    protected $fillable = ['leave_type', 'user_id', 'status', 'leave_message', 'action_message', 'action_by'];

}
