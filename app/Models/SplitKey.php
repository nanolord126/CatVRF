<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\AES256EncryptedCast;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Split Key Model
 *
 * Represents a cryptographic split key for enhanced security.
 * Server Part (encrypted) + Device Part (Secure Enclave/TPM) = Full Key.
 * Full key is used for signing session tokens and sensitive operations.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 * CRITICAL: 7-day activity window, device attestation, fraud integration
 */
final class SplitKey extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'tenant_id',
        'server_part_encrypted',
        'key_hash',
        'last_activity_at',
        'expires_at',
        'status',
        'attestation_data',
        'metadata',
    ];

    protected $casts = [
        'last_activity_at' => 'datetime',
        'expires_at' => 'datetime',
        'attestation_data' => 'array',
        'metadata' => 'array',
        'server_part_encrypted' => AES256EncryptedCast::class,
    ];

    /**
     * Relationship to the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship to the tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope for active keys
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('expires_at', '>', CarbonImmutable::now());
    }

    /**
     * Scope for expired keys
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', CarbonImmutable::now())
            ->where('status', 'active');
    }

    /**
     * Scope for revoked keys
     */
    public function scopeRevoked($query)
    {
        return $query->where('status', 'revoked');
    }

    /**
     * Scope for user keys
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for tenant keys
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Check if key is currently active
     */
    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->expires_at->isFuture();
    }

    /**
     * Check if key is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if key is revoked
     */
    public function isRevoked(): bool
    {
        return $this->status === 'revoked';
    }

    /**
     * Get remaining time in seconds
     */
    public function getRemainingSeconds(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        return max(0, CarbonImmutable::now()->diffInSeconds($this->expires_at));
    }

    /**
     * Get remaining time in human-readable format
     */
    public function getRemainingTimeForHumans(): string
    {
        $seconds = $this->getRemainingSeconds();

        if ($seconds === 0) {
            return 'Истёк';
        }

        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($days > 0) {
            return "{$days} дн. {$hours} ч.";
        }

        if ($hours > 0) {
            return "{$hours} ч. {$minutes} мин.";
        }

        return "{$minutes} мин.";
    }

    /**
     * Mark key as expired
     */
    public function markAsExpired(): bool
    {
        return $this->update([
            'status' => 'expired',
        ]);
    }

    /**
     * Revoke key (e.g., on fraud detection)
     */
    public function revoke(string $reason = 'Security revocation'): bool
    {
        return $this->update([
            'status' => 'revoked',
            'metadata' => array_merge($this->metadata ?? [], [
                'revoked_at' => CarbonImmutable::now()->toIso8601String(),
                'revocation_reason' => $reason,
            ]),
        ]);
    }

    /**
     * Update activity timestamp and extend expiration
     */
    public function updateActivity(): bool
    {
        return $this->update([
            'last_activity_at' => CarbonImmutable::now(),
            'expires_at' => CarbonImmutable::now()->addDays(7),
        ]);
    }

    /**
     * Check if key needs rotation (7 days inactive)
     */
    public function needsRotation(): bool
    {
        if ($this->last_activity_at === null) {
            return true;
        }

        $inactiveDays = CarbonImmutable::now()->diffInDays($this->last_activity_at);

        return $inactiveDays >= 7;
    }

    /**
     * Get device attestation data
     */
    public function getAttestationData(): ?array
    {
        return $this->attestation_data;
    }

    /**
     * Verify device attestation (placeholder for production implementation)
     */
    public function verifyAttestation(array $clientAttestation): bool
    {
        // In production, implement full attestation verification:
        // 1. Verify attestation statement format
        // 2. Verify signature with attestation certificate
        // 3. Verify trust chain
        // 4. Check device flags (Secure Enclave, TPM, etc.)

        return true;
    }
}
