<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ConsentStatus;
use App\Enums\ConsentType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * User Consent Model for 152-FZ Compliance
 * 
 * Tracks granular consent for each type of personal data processing.
 * Supports versioning, withdrawal, and automatic data destruction scheduling.
 */
final class UserConsent extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'tenant_id',
        'consent_type',
        'status',
        'consent_text',
        'consent_version',
        'consent_purposes',
        'ip_address',
        'user_agent',
        'device_fingerprint',
        'signature_method',
        'granted_at',
        'withdrawn_at',
        'expires_at',
        'withdrawal_reason',
        'withdrawn_by',
        'data_purge_scheduled_at',
        'data_purged_at',
        'purge_job_id',
        'metadata',
    ];

    protected $casts = [
        'consent_type' => ConsentType::class,
        'status' => ConsentStatus::class,
        'consent_purposes' => 'json',
        'granted_at' => 'datetime',
        'withdrawn_at' => 'datetime',
        'expires_at' => 'datetime',
        'data_purge_scheduled_at' => 'datetime',
        'data_purged_at' => 'datetime',
        'metadata' => 'json',
    ];

    protected $table = 'user_consents';

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

    public function withdrawnByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'withdrawn_by');
    }

    // ========================
    // SCOPES
    // ========================

    public function scopeActive($query)
    {
        return $query->where('status', ConsentStatus::GRANTED)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', CarbonImmutable::now());
            });
    }

    public function scopeForType($query, ConsentType $type)
    {
        return $query->where('consent_type', $type);
    }

    public function scopeBiometric($query)
    {
        return $query->where('consent_type', 'like', 'biometric_%');
    }

    public function scopePendingPurge($query)
    {
        return $query->where('status', ConsentStatus::WITHDRAWN)
            ->whereNull('data_purged_at')
            ->whereNotNull('data_purge_scheduled_at');
    }

    // ========================
    // BUSINESS LOGIC
    // ========================

    /**
     * Check if consent is currently active
     */
    public function isActive(): bool
    {
        return $this->status === ConsentStatus::GRANTED
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * Check if consent is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Check if consent requires written form (biometric)
     */
    public function requiresWrittenForm(): bool
    {
        return $this->consent_type->isBiometric();
    }

    /**
     * Withdraw consent and schedule data destruction
     */
    public function withdraw(?string $reason = null, ?int $withdrawnBy = null): bool
    {
        $this->update([
            'status' => ConsentStatus::WITHDRAWN,
            'withdrawn_at' => CarbonImmutable::now(),
            'withdrawal_reason' => $reason ?? 'User withdrawal',
            'withdrawn_by' => $withdrawnBy ?? $this->user_id,
            'data_purge_scheduled_at' => CarbonImmutable::now()->addDays(30), // 152-FZ: 30 days for destruction
        ]);

        // Dispatch job for data destruction
        \App\Jobs\PersonalData\PurgePersonalDataJob::dispatch($this->id)
            ->delay(now()->addDays(30));

        return true;
    }

    /**
     * Revoke consent (administrative action)
     */
    public function revoke(string $reason, int $revokedBy): bool
    {
        return $this->update([
            'status' => ConsentStatus::REVOKED,
            'withdrawn_at' => CarbonImmutable::now(),
            'withdrawal_reason' => $reason,
            'withdrawn_by' => $revokedBy,
            'data_purge_scheduled_at' => CarbonImmutable::now()->addDays(30),
        ]);
    }

    /**
     * Mark consent as expired
     */
    public function markAsExpired(): bool
    {
        return $this->update([
            'status' => ConsentStatus::EXPIRED,
        ]);
    }

    /**
     * Record that data has been purged
     */
    public function markDataAsPurged(string $jobId): bool
    {
        return $this->update([
            'data_purged_at' => CarbonImmutable::now(),
            'purge_job_id' => $jobId,
        ]);
    }

    /**
     * Get days until data purge
     */
    public function daysUntilPurge(): ?int
    {
        if ($this->data_purge_scheduled_at === null) {
            return null;
        }

        return CarbonImmutable::now()->diffInDays($this->data_purge_scheduled_at, false);
    }

    /**
     * Get consent type enum
     */
    public function getConsentTypeEnum(): ConsentType
    {
        return ConsentType::from($this->consent_type->value);
    }

    /**
     * Get retention period for this consent type
     */
    public function getRetentionDays(): int
    {
        return $this->consent_type->retentionDays();
    }

    /**
     * Check if data should be purged
     */
    public function shouldPurge(): bool
    {
        if ($this->status !== ConsentStatus::WITHDRAWN && $this->status !== ConsentStatus::REVOKED) {
            return false;
        }

        if ($this->data_purged_at !== null) {
            return false;
        }

        if ($this->data_purge_scheduled_at === null) {
            return false;
        }

        return $this->data_purge_scheduled_at->isPast();
    }

    protected static function boot()
    {
        parent::boot();

        // Generate UUID on create
        self::creating(function ($model) {
            $model->uuid ??= \Illuminate\Support\Str::uuid()->toString();
        });
    }
}
