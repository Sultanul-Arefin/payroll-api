<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Modules\Agenda\Entities\Agenda;
use Modules\Company\Entities\Company;
use Modules\Department\Entities\Department;
use Modules\Designation\Entities\Designation;
use Modules\EmployeeSalaryItems\Entities\EmployeeSalaryItem;
use Modules\Payslip\Entities\Payslip;
use Modules\ProjectManagement\Entities\TaskAssociatedEmployee;
use Modules\Role\Entities\Role;
use Modules\User\Entities\UserAttachment;
use Modules\User\Entities\UserDetails;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes, Notifiable;

    public const USER_ACTIVE = 1;
    public const USER_DISABLE = 0;
    public const USER_PENDING = 2;

    public const ADMIN = 1;
    public const DEPARTMENT_MANAGER = 2;
    public const EMPLOYEE = 3;
    public const MANAGER = 4;

    public const EMPLOYEE_TYPE_FULL_TIME = 1;
    public const EMPLOYEE_TYPE_PART_TIME = 2;
    public const EMPLOYEE_TYPE_FLEXI_TIME = 3;
    public const EMPLOYEE_TYPE_CONTRACTUAL = 4;

    public const STAFF_INTERACTION_PANEL_GIVEN = 1;
    public const STAFF_INTERACTION_PANEL_NOT_GIVEN = 0;

    protected string $guard_name = 'sanctum';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'designation_id',
        'assign_to',
        'department_id',
        'email',
        'password',
        'status',
        'staff_interaction_panel_status',
        'last_login',
        'company_id',
        'role_id',
        'customer_id',
        'employee_type',
        'national_tax',
        'regional_tax'
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'status' => 'integer',
        'email_verified_at' => 'datetime',
        'role_id' => 'integer',
    ];

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

    public function active_company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id')->where('status', Company::ACTIVE);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function user_details(): HasOne
    {
        return $this->hasOne(UserDetails::class, 'user_id', 'id');
    }

    public function user_attachments(): HasMany
    {
        return $this->hasMany(UserAttachment::class, 'user_id', 'id');
    }

    public function assign_to_user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assign_to', 'id');
    }

    public function tasks_associated(): HasMany
    {
        return $this->hasMany(TaskAssociatedEmployee::class, 'user_id', 'id');
    }

    public function salary_items(): HasMany
    {
        return $this->hasMany(EmployeeSalaryItem::class, 'employee_id', 'id')
                ->orWhere(function ($query) {
                    $query->where('employee_id', $this->id) // Get items where employee_id matches user id
                          ->orWhere(function ($query) {
                              $query->whereNull('employee_id') // Get items where employee_id is null
                                    ->where('is_general', 1) // and is_general is 1
                                    ->where('company_id', auth()->user()->company_id);
                          });
                });
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class, 'employee_id', 'id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'designation_id', 'id');
    }

    /**
     * Send a password reset notification to the user.
     *
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function userAttachment(): HasMany
    {
        return $this->hasMany(UserAttachment::class, 'user_id', 'id');
    }

    public function get_user_role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function userRole(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => ($value.'3')
        );
    }

    /**
     * @return HasMany
     */
    public function agendas(): HasMany
    {
        return $this->hasMany(Agenda::class, 'user_id', 'id');
    }
}
