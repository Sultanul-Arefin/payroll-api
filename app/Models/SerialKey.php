<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SerialKey extends Model
{
    use HasFactory;

    public const AMAZON_STORE = 'amazon';
}
