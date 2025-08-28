<?php

namespace Modules\IRS\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Form940Transmission extends Model
{
    use HasFactory;

    protected $table = 'form_940_transmissions';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'submission_id',
        'record_id',
        'status',
        'attempts',
        'transmitted_at',
        'error_message',
        'response'
    ];

    protected $casts = [
        'response' => 'array',
        'transmitted_at' => 'datetime',
    ];
}
