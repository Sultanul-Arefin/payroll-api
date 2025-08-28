<?php

namespace Modules\IRS\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Filing extends Model
{
    use HasFactory;
    protected $table = 'irs_filings';
    protected $fillable = [
        'form_type',
        'quarter',
        'created_date',
        'status',
        'submission_id',
        'record_id',
        'api_response',
    ];

    protected $casts = [
    'api_response' => 'array',
    ];

     public function logs()
    {
        return $this->hasMany(IrsFilingLog::class);
    }
}
