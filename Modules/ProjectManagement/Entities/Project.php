<?php

namespace Modules\ProjectManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [];
    
    protected static function newFactory()
    {
        return \Modules\ProjectManagement\Database\factories\ProjectFactory::new();
    }

    public const CANCELLED = 0;
    public const COMPLETED = 1;
    public const PROCESSING = 2;
}
