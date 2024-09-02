<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'logo',
        'loader',
        'favicon',
        'title'
    ];

    public function getChangedLogoAttribute()
    {
        return $this->logo ? env('APP_FRONTEND_URL').'/'.'storage/'.$this->logo : null;
    }

    public function getChangedLoaderAttribute()
    {
        return $this->loader ? env('APP_FRONTEND_URL').'/'.'storage/'.$this->loader : null;
    }

    public function getChangedFaviconAttribute()
    {
        return $this->favicon ? env('APP_FRONTEND_URL').'/'.'storage/'.$this->favicon : null;
    }
}
