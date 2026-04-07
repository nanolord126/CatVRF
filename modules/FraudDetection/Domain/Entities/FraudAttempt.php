<?php

declare(strict_types=1);

namespace Modules\FraudDetection\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

final class FraudAttempt extends Model
{
    use HasFactory;

    protected $table = 'fraud_attempts';

    protected $fillable = [
        'transaction_id',
        'user_id',
        'score',
        'details',
        'correlation_id',
        'uuid',
        'tags',
    ];

    protected $casts = [
        'score' => 'float',
        'details' => 'json',
        'tags' => 'json',
    ];
}
