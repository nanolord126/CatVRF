<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence;

use Carbon\CarbonImmutable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Dead Letter Queue Model
 * Stores events that failed after max retries
 */
final class DeadLetterQueue extends Model
{
    use SoftDeletes;

    // Failure reasons
    public const FAILURE_MAX_RETRIES = 'max_retries_exceeded';

    public const FAILURE_EVENT_CLASS_NOT_FOUND = 'event_class_not_found';

    public const FAILURE_INVALID_PAYLOAD = 'invalid_payload';

    public const FAILURE_DISPATCH_ERROR = 'dispatch_error';

    public const FAILURE_TIMEOUT = 'timeout';

    public const FAILURE_VALIDATION = 'validation_failed';

    protected $table = 'dead_letter_queue';

    protected $fillable = [
        'id',
        'original_outbox_id',
        'event_type',
        'payload',
        'correlation_id',
        'user_id',
        'tenant_id',
        'error_message',
        'error_trace',
        'retry_count',
        'failure_reason',
        'failed_at',
        'is_processed',
        'processed_at',
        'processed_by',
    ];

    protected $casts = [
        'payload' => 'array',
        'failed_at' => 'datetime',
        'processed_at' => 'datetime',
        'is_processed' => 'boolean',
    ];

    public function markAsProcessed(string $processedBy): void
    {
        $this->update([
            'is_processed' => true,
            'processed_at' => CarbonImmutable::now(),
            'processed_by' => $processedBy,
        ]);
    }

    public function scopeUnprocessed($query)
    {
        return $query->where('is_processed', false);
    }

    public function scopeByEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeByFailureReason($query, string $reason)
    {
        return $query->where('failure_reason', $reason);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('failed_at', '>=', CarbonImmutable::now()->subDays($days));
    }
}
