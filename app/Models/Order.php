<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->order_id)) {
                do {
                    $uniqueOrderId = 'order-' . strtolower(Str::random(8));
                } while (self::where('order_id', $uniqueOrderId)->exists());

                $model->order_id = $uniqueOrderId;
            }
        });
    }
}
