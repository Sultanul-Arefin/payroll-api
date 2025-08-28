<?php

namespace Modules\IRS\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IrsFilingLog extends Model
{
    use HasFactory;
    protected $table = 'irs_filings_logs';
    protected $fillable = [
        'filing_id',
        'action',
        'request_data',
        'response_data',
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
    ];

    public function filing()
    {
        return $this->belongsTo(Filing::class);
    }
}
