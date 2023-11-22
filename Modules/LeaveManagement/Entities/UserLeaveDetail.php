<?php

namespace Modules\LeaveManagement\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLeaveDetail extends Model
{
    use HasFactory;

    protected $fillable = ['user_leaves_id', 'dates'];

    public function user_leave(): BelongsTo
    {
        return $this->belongsTo(UserLeave::class, 'user_leaves_id', 'id');
    }
}
