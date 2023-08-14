<?php

namespace Modules\Designation\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Designation extends Model
{
    use HasFactory;

    protected $fillable = ['company_id','name'];
    
    protected static function newFactory()
    {
        return \Modules\Designation\Database\factories\DesignationFactory::new();
    }

    /**
     * @return HasMany
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'designation_id', 'id');
    }
}