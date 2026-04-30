<?php

declare(strict_types=1);

namespace App\Domains\Sports\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Database\Factories\FraudPenaltyFactory;

final class FraudPenalty extends Model
{
    use HasFactory;

    protected $table = 'sports_fraud_penalties';

    protected $fillable = [
        'tenant_id',
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

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (app()->bound('tenant') && app('tenant') instanceof \App\Models\Tenant) {
                $query->where('tenant_id', app('tenant')->id);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory()
    {
        return FraudPenaltyFactory::new();
    }
}
