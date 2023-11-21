<?php

namespace Modules\ProjectManagement\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectAssociatedEmployee extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'assigned_employees',
    ];
}
