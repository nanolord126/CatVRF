<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperNotificationCampaign
 */
final class NotificationCampaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'audience_id',
        'name',
        'message',
        'type',
        'channel',
        'status',
        'scheduled_at',
        'sent_at',
        'total_recipients',
        'sent_count',
        'delivered_count',
        'opened_count',
        'reacted_count',
        'compliance_checks',
        'compliance_passed',
        'compliance_notes',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'compliance_checks' => 'array',
        'compliance_passed' => 'boolean',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public const TYPE_PROMOTION = 'promotion';
    public const TYPE_INFORMATION = 'information';
    public const TYPE_REMINDER = 'reminder';
    public const TYPE_ALERT = 'alert';

    public const CHANNEL_IN_APP = 'in_app';
    public const CHANNEL_EMAIL = 'email';
    public const CHANNEL_PUSH = 'push';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_SENDING = 'sending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function audience(): BelongsTo
    {
        return $this->belongsTo(InternalAudience::class, 'audience_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeCompliant($query)
    {
        return $query->where('compliance_passed', true);
    }

    public function scopePendingReview($query)
    {
        return $query->where('status', self::STATUS_PENDING_REVIEW);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED)
            ->where('scheduled_at', '>=', now());
    }

    public function getDeliveryRateAttribute(): float
    {
        if ($this->sent_count === 0) {
            return 0;
        }

        return round(($this->delivered_count / $this->sent_count) * 100, 2);
    }

    public function getOpenRateAttribute(): float
    {
        if ($this->delivered_count === 0) {
            return 0;
        }

        return round(($this->opened_count / $this->delivered_count) * 100, 2);
    }

    public function getReactionRateAttribute(): float
    {
        if ($this->opened_count === 0) {
            return 0;
        }

        return round(($this->reacted_count / $this->opened_count) * 100, 2);
    }

    public function isPromotional(): bool
    {
        return $this->type === self::TYPE_PROMOTION;
    }

    public function requiresApproval(): bool
    {
        return $this->isPromotional();
    }

    public function submitForReview(): void
    {
        $this->update(['status' => self::STATUS_PENDING_REVIEW]);
    }

    public function approve(int $approvedBy): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
    }

    public function reject(string $notes = ''): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'compliance_notes' => $notes,
        ]);
    }
}
