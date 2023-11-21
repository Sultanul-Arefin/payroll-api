<?php

namespace Modules\Attendance\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'dates',
        'user_id',
        'status',
    ];

    protected static function newFactory()
    {
        return \Modules\Attendance\Database\factories\AttendanceFactory::new();
    }

    public const ABSENT = 0;

    public const PRESENT = 1;

    public const PENDING = 2;

    public const RESTRICTED = 3;

    public function attendance_details(): HasMany
    {
        return $this->hasMany(AttendanceDetail::class, 'attendance_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
