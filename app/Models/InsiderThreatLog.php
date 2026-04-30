<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class InsiderThreatLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'performed_by',
        'action_type',
        'resource_type',
        'resource_id',
        'anomaly_score',
        'severity',
        'was_blocked',
        'block_reason',
        'requires_review',
        'is_reviewed',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'action_details',
        'context',
    ];

    protected $casts = [
        'anomaly_score' => 'decimal:4',
        'was_blocked' => 'boolean',
        'requires_review' => 'boolean',
        'is_reviewed' => 'boolean',
        'reviewed_at' => 'datetime',
        'action_details' => 'json',
        'context' => 'json',
    ];

    protected $table = 'insider_threat_logs';

    // ========================
    // RELATIONSHIPS
    // ========================

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // ========================
    // SCOPES
    // ========================

    public function scopeHighSeverity($query)
    {
        return $query->whereIn('severity', ['high', 'critical']);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopeBlocked($query)
    {
        return $query->where('was_blocked', true);
    }

    public function scopeRequiresReview($query)
    {
        return $query->where('requires_review', true)->where('is_reviewed', false);
    }

    public function scopeReviewed($query)
    {
        return $query->where('is_reviewed', true);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByActionType($query, string $actionType)
    {
        return $query->where('action_type', $actionType);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', CarbonImmutable::now()->subDays($days));
    }

    public function scopeHighRisk($query, float $threshold = 0.8)
    {
        return $query->where('anomaly_score', '>=', $threshold);
    }

    // ========================
    // HELPER METHODS
    // ========================

    /**
     * Mark as reviewed
     */
    public function markAsReviewed(int $reviewedById, ?string $notes = null): bool
    {
        return $this->update([
            'is_reviewed' => true,
            'reviewed_by' => $reviewedById,
            'reviewed_at' => CarbonImmutable::now(),
            'review_notes' => $notes,
        ]);
    }

    /**
     * Check if this is a critical threat
     */
    public function isCritical(): bool
    {
        return $this->severity === 'critical' || $this->anomaly_score >= 0.9;
    }

    /**
     * Check if this is a high threat
     */
    public function isHigh(): bool
    {
        return $this->severity === 'high' || $this->anomaly_score >= 0.7;
    }

    /**
     * Get action summary
     */
    public function getActionSummary(): string
    {
        return sprintf(
            '%s on %s:%d (score: %.4f)',
            $this->action_type,
            $this->resource_type ?? 'unknown',
            $this->resource_id ?? 0,
            $this->anomaly_score
        );
    }

    /**
     * Get IP address from context
     */
    public function getIpAddress(): ?string
    {
        return $this->context['ip_address'] ?? null;
    }

    /**
     * Get device fingerprint from context
     */
    public function getDeviceFingerprint(): ?string
    {
        return $this->context['device_fingerprint'] ?? null;
    }
}
