<?php

namespace Modules\LeaveManagement\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLeaveDetail extends Model
{
    use HasFactory;

    protected $fillable = ['user_leaves_id', 'dates'];
}
