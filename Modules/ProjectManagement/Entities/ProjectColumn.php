<?php

namespace Modules\ProjectManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProjectColumn extends Model
{
    use HasFactory;

    protected $fillable = [
        'column_name',
        'company_id',
        'created_by'
    ];
    
    protected static function newFactory()
    {
        return \Modules\ProjectManagement\Database\factories\ProjectColumnFactory::new();
    }
}
