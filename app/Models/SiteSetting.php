<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class SiteSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'logo',
        'loader',
        'favicon',
        'title'
    ];

    public function Logo(): Attribute
    {
        return Attribute::make(
            get: fn($value) => ($value !=null ? asset('storage').'/'.$value : null )
        );
    }
    public function Loader(): Attribute
    {
        return Attribute::make(
            get: fn($value) => ($value !=null ? asset('storage').'/'.$value : null )
        );
    }
    public function Favicon(): Attribute
    {
        return Attribute::make(
            get: fn($value) => ($value !=null ? asset('storage').'/'.$value : null )
        );
    }
}
