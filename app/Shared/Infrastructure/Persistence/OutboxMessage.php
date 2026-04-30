<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence;

use Carbon\CarbonImmutable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Outbox Pattern: Stores events before publishing to ensure guaranteed delivery
 */
final class OutboxMessage extends Model
{
    use HasUuids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_FAILED = 'failed';

    public const PRIORITY_LOW = 1;

    public const PRIORITY_NORMAL = 5;

    public const PRIORITY_HIGH = 8;

    public const PRIORITY_CRITICAL = 10;

    protected $table = 'outbox_messages';

    protected $fillable = [
        'id',
        'event_type',
        'payload',
        'correlation_id',
        'causation_id',
        'user_id',
        'tenant_id',
        'status',
        'published_at',
        'processing_attempts',
        'last_error',
        'priority',
        'queue',
    ];

    protected $casts = [
        'payload' => 'array',
        'published_at' => 'datetime',
        'processing_attempts' => 'integer',
    ];

    public function markAsPublished(): void
    {
        $this->update([
            'status' => self::STATUS_PUBLISHED,
            'published_at' => CarbonImmutable::now(),
        ]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'processing_attempts' => $this->processing_attempts + 1,
            'last_error' => $error,
        ]);
    }

    public function incrementAttempts(): void
    {
        $this->increment('processing_attempts');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function shouldRetry(int $maxAttempts = 3): bool
    {
        return $this->processing_attempts < $maxAttempts;
    }
}
