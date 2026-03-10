<?php

namespace app\Models\Common;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class BusinessReport extends Model
{
    protected $fillable = [
        'type', // daily, weekly
        'report_date',
        'data',
        'is_sent',
        'sent_at',
    ];

    protected $casts = [
        'data' => 'array',
        'report_date' => 'date',
        'sent_at' => 'datetime',
        'is_sent' => 'boolean',
    ];
}
