<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RotaAssignedLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'employee_id'
    ];
}
