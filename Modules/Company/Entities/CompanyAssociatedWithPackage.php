<?php

namespace Modules\Company\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompanyAssociatedWithPackage extends Model
{
    use HasFactory;

    protected $table = "company_associated_with_package";

    protected $fillable = [];
    
    protected static function newFactory()
    {
        return \Modules\Company\Database\factories\CompanyAssociatedWithPackageFactory::new();
    }
}
