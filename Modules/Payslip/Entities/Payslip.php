<?php

namespace Modules\Payslip\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payslip extends Model
{
    use HasFactory;

    protected $fillable = [];
    
    /**
     * changes while using uuid
     */
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function($model){
            $model->id = (string) \Illuminate\Support\Str::uuid();
        });
    }
}
