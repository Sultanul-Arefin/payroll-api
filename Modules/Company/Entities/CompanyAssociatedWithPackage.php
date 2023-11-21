<?php

namespace Modules\Company\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Package\Entities\Package;

class CompanyAssociatedWithPackage extends Model
{
    use HasFactory;

    protected $table = 'company_associated_with_package';

    protected $fillable = [];

    protected static function newFactory()
    {
        return \Modules\Company\Database\factories\CompanyAssociatedWithPackageFactory::new();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'package_id', 'id');
    }
}
