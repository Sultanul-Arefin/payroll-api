<?php

namespace Modules\IRS\Entities;

use Illuminate\Database\Eloquent\Model;

class TaxbanditsPdfWebhook extends Model
{
    protected $fillable = [
        'submission_id',
        'record_id',
        'pdf_url',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}