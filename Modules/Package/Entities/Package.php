<?php

namespace Modules\Package\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Package\Database\factories\PackageFactory;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [];

    public const PRICE_CHARGED_1 = "Monthly";
    public const PRICE_CHARGED_2 = "Quarterly";
    public const PRICE_CHARGED_3 = "Annually";

    protected static function newFactory()
    {
        return PackageFactory::new();
    }

    /**
     * @return HasOne
     */
    function associated_package(): HasOne {
        return $this->hasOne(CompanyAssociatedWithPackage::class, 'package_id', 'id');
    }
}
