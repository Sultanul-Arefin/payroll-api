<?php

namespace Modules\Payslip\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    /**
     * other methods
     */
    
    /**
     * @return belongsTo
     */
    function employee(): BelongsTo {
        return $this->belongsTo(User::class, 'employee_id', 'id');
    }
}
