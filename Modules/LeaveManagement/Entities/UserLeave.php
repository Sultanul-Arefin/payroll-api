<?php

namespace Modules\LeaveManagement\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\SalaryItemsName\Entities\SalaryItemsName;

class UserLeave extends Model
{
    use HasFactory;

    public const APPROVED = 1;

    public const PENDING = 2;

    public const DENIED = 3;

    protected $fillable = ['leave_type', 'user_id', 'status', 'leave_message', 'action_message', 'action_by'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function leave_details()
    {
        return $this->hasMany(UserLeaveDetail::class, 'user_leaves_id', 'id');
    }

    public function salary_item()
    {
        return $this->belongsTo(SalaryItemsName::class, 'leave_type', 'id');
    }
}
