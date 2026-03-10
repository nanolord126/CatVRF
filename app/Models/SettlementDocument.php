<?php

namespace App\Models;

use App\Traits\HasCorrelationId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SettlementDocument extends Model
{
    use HasFactory, SoftDeletes, HasCorrelationId;

    protected $fillable = [
        'type',
        'number',
        'document_date',
        'amount',
        'currency',
        'status',
        'file_path',
        'signed_file_path',
        'meta',
        'correlation_id'
    ];

    protected $casts = [
        'document_date' => 'date',
        'amount' => 'decimal:2',
        'meta' => 'array',
    ];
}
