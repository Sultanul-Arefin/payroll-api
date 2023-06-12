<?php

namespace Modules\Designation\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Designation extends Model
{
    use HasFactory;

    protected $fillable = ['company_id','name'];
    
    protected static function newFactory()
    {
        return \Modules\Designation\Database\factories\DesignationFactory::new();
    }
}