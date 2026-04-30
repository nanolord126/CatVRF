<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * FraudAttempt — запись о подозрительном действии.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * @property int $id
 * @property int|null $tenant_id
 * @property int|null $user_id
 * @property string $operation_type
 * @property string $ip_address
 * @property string $device_fingerprint
 * @property string $correlation_id
 * @property float|null $ml_score
 * @property string|null $ml_version
 * @property array|null $features
 * @property string $decision block|review|allow
 * @property string|null $reason
 * @property Carbon|null $blocked_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class FraudAttempt extends Model
{
    protected $table = 'fraud_attempts';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'user_id',
        'operation_type',
        'ip_address',
        'device_fingerprint',
        'correlation_id',
        'ml_score',
        'ml_version',
        'features',
        'decision',
        'reason',
        'blocked_at',
    ];

    protected $casts = [
        'ml_score'      => 'float',
        'features' => 'array',
        'blocked_at'    => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ── Scopes ───────────────────────────────────────────────

    public function scopeBlocked($query): Builder
    {
        return $query->where('decision', 'block');
    }

    public function scopeToday($query): Builder
    {
        return $query->where('created_at', '>=', CarbonImmutable::now()->startOfDay());
    }

    public function scopeHighRisk($query): Builder
    {
        return $query->where('ml_score', '>=', 0.65);
    }
}
