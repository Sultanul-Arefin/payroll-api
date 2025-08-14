<?php

namespace Modules\IRS\Entities;

use Illuminate\Database\Eloquent\Model;

class IrsTransmission extends Model
{
            protected $fillable = [
           
            'submission_id',
            'record_id',
            'status',
            'attempts',
            'transmitted_at',
            'error_message',
            'response',
        ];

        protected $casts = [
            
            'submission_id' => 'string',
            'record_id' => 'string',
            'response' => 'array',
            'transmitted_at' => 'datetime',
        ];
}