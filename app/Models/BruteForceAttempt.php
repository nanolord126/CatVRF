<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BruteForceAttempt extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'email',
        'ip_address',
        'user_agent',
        'device_fingerprint',
        'attempt_type',
        'was_successful',
        'was_blocked',
        'block_reason',
        'metadata',
        'tenant_id',
    ];

    protected $casts = [
        'was_successful' => 'boolean',
        'was_blocked' => 'boolean',
        'metadata' => 'json',
    ];

    protected $table = 'brute_force_attempts';

    // ========================
    // RELATIONSHIPS
    // ========================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ========================
    // SCOPES
    // ========================

    public function scopeSuccessful($query)
    {
        return $query->where('was_successful', true);
    }

    public function scopeBlocked($query)
    {
        return $query->where('was_blocked', true);
    }

    public function scopeByIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }

    public function scopeByEmail($query, string $email)
    {
        return $query->where('email', $email);
    }

    public function scopeByDevice($query, string $fingerprint)
    {
        return $query->where('device_fingerprint', $fingerprint);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('attempt_type', $type);
    }

    public function scopeRecent($query, int $minutes = 5)
    {
        return $query->where('created_at', '>=', CarbonImmutable::now()->subMinutes($minutes));
    }

    // ========================
    // HELPER METHODS
    // ========================

    /**
     * Check if this attempt was blocked by rate limiting
     */
    public function isRateLimited(): bool
    {
        return $this->was_blocked && $this->block_reason === 'rate_limit';
    }

    /**
     * Check if this attempt was blocked by HIBP check
     */
    public function isHibpBlocked(): bool
    {
        return $this->was_blocked && $this->block_reason === 'hibp';
    }

    /**
     * Check if this attempt was blocked by ML fraud detection
     */
    public function isMlBlocked(): bool
    {
        return $this->was_blocked && $this->block_reason === 'fraud_ml';
    }

    /**
     * Get risk score from metadata
     */
    public function getRiskScore(): float
    {
        return (float) ($this->metadata['risk_score'] ?? 0.0);
    }

    /**
     * Get geo location from metadata
     */
    public function getGeoLocation(): ?array
    {
        return $this->metadata['geo'] ?? null;
    }
}
