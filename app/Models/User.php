<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Modules\Company\Entities\Company;
use Modules\Department\Entities\Department;
use Modules\Designation\Entities\Designation;
use Modules\EmployeeSalaryItems\Entities\EmployeeSalaryItem;
use Modules\Payslip\Entities\Payslip;
use Modules\Permission\Entities\Permission;
use Modules\ProjectManagement\Entities\TaskAssociatedEmployee;
use Modules\User\Entities\UserAttachment;
use Modules\User\Entities\UserDetails;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes, Notifiable;

    public const USER_ACTIVE = 1;
    public const USER_DISABLE = 0;
    public const USER_PENDING = 2;

    public const SUPER_ADMIN = 1;
    public const ADMIN = 2;
    public const DEPARTMENT_MANAGER = 3;
    public const HR = 4;
    public const EMPLOYEE = 5;


    protected string $guard_name = 'sanctum';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'designation_id',
        'assign_to',
        'department_id',
        'email',
        'password',
        'status',
        'last_login',
        'company_id',
        'user_role'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'integer',
        'email_verified_at' => 'datetime',
    ];

    function setPasswordAttribute($value) {
        $this->attributes['password'] = Hash::make($value);
    }

    /**
     * @return BelongsTo
     */
    public function active_company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id')->where('status', Company::ACTIVE);
    }

    /**
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * @return HasOne
     */
    public function user_details(): HasOne
    {
        return $this->hasOne(UserDetails::class, 'user_id', 'id');
    }

    /**
     * @return belongsTo
     */
    public function assign_to_user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assign_to', 'id');
    }

    /**
     * @return HasMany
     */
    function tasks_associated(): HasMany {
        return $this->hasMany(TaskAssociatedEmployee::class, 'user_id', 'id');
    }

    /**
     * @return HasMany
     */
    function salary_items(): HasMany {
        return $this->hasMany(EmployeeSalaryItem::class, 'employee_id', 'id');
    }

    /**
     * @return HasMany
     */
    function paslips(): HasMany {
        return $this->hasMany(Payslip::class, 'employee_id', 'id');
    }

    /**
     * @return belongsTo
     */
    function department(): BelongsTo {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    /**
     * @return belongsTo
     */
    function designation(): BelongsTo {
        return $this->belongsTo(Designation::class, 'designation_id', 'id');
    }

    /**
     * Send a password reset notification to the user.
     *
     * @param string $token
     * @return void
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
    public function userAttachment(){
        return $this->hasMany(UserAttachment::class, 'user_id', 'id');
    }

}
