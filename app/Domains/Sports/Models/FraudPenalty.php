<?php

declare(strict_types=1);

namespace App\Domains\Sports\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FraudPenalty extends Model
{
    use HasFactory;

    protected $table = 'sports_fraud_penalties';

    protected $fillable = [
        'user_id',
        'fraud_type',
        'risk_score',
        'penalty_type',
        'penalty_details',
        'correlation_id',
    ];

    protected $casts = [
        'risk_score' => 'decimal:2',
        'penalty_details' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    protected static function newFactory()
    {
        return \Database\Factories\FraudPenaltyFactory::new();
    }
}
