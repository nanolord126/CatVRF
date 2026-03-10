<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AIReport extends Model
{
    protected $table = 'ai_reports';

    protected $fillable = [
        'type',
        'report_date',
        'data',
        'pdf_path',
        'sent_at'
    ];

    protected $casts = [
        'report_date' => 'date',
        'data' => 'array',
        'sent_at' => 'datetime'
    ];
}
