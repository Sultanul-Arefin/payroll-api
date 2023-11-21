<?php

namespace Modules\Department\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_name',
        'company_id',
        'parent_id',
    ];

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class, 'parent_id', 'id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'department_id', 'id');
    }
}
