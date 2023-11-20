<?php

namespace Modules\SalaryItemsName\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveSalaryItems extends Model
{
    use HasFactory;

    protected $fillable = [
        'salary_items_id',
        'no_of_days',
    ];

    protected static function newFactory()
    {
        return \Modules\SalaryItemsName\Database\factories\LeaveSalaryItemsFactory::new();
    }

    public function salary_items_name(): BelongsTo
    {
        return $this->belongsTo(SalaryItemsName::class, 'salary_items_id', 'id');
    }
}
