<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AccountRecoveryLog extends Model
{
    use HasFactory;

    public const METHOD_EMAIL = 'email';

    public const METHOD_SMS = 'sms';

    public const METHOD_BACKUP_CODE = 'backup_code';

    public const METHOD_AI_FACE = 'ai_face';

    public const METHOD_MANUAL_REVIEW = 'manual_review';

    public const STATUS_INITIATED = 'initiated';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_BLOCKED = 'blocked';

    protected $fillable = [
        'user_id',
        'tenant_id',
        'method',
        'risk_score',
        'status',
        'ip_address',
        'user_agent',
        'device_fingerprint',
        'metadata',
        'initiated_at',
        'verified_at',
        'completed_at',
        'failure_reason',
    ];

    protected $casts = [
        'risk_score' => 'decimal:2',
        'metadata' => 'array',
        'initiated_at' => 'datetime',
        'verified_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isBlocked(): bool
    {
        return $this->status === self::STATUS_BLOCKED;
    }

    public function isHighRisk(): bool
    {
        return $this->risk_score >= 0.70;
    }

    public function isMediumRisk(): bool
    {
        return $this->risk_score >= 0.40 && $this->risk_score < 0.70;
    }

    public function isLowRisk(): bool
    {
        return $this->risk_score < 0.40;
    }

    public function getDurationInSeconds(): int
    {
        if (! $this->completed_at || ! $this->initiated_at) {
            return 0;
        }

        return $this->completed_at->diffInSeconds($this->initiated_at);
    }
}
