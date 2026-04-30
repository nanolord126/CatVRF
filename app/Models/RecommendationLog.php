<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

final class RecommendationLog extends Model
{
    protected $table = 'recommendation_logs';

    protected $fillable = [
        'user_id',
        'tenant_id',
        'vertical',
        'recommended_items',
        'score',
        'source',
        'correlation_id',
        'metadata',
    ];

    protected $casts = [
        'recommended_items' => 'array',
        'score'             => 'float',
        'metadata'          => 'array',
    ];

    // --- Relations ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // --- Scopes ---

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForVertical(Builder $query, string $vertical): Builder
    {
        return $query->where('vertical', $vertical);
    }

    public function scopeByCorrelation(Builder $query, string $correlationId): Builder
    {
        return $query->where('correlation_id', $correlationId);
    }
}
