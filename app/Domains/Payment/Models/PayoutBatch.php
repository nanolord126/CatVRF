<?php

declare(strict_types=1);

namespace App\Domains\Payment\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Payout Batch - groups multiple payouts for bulk processing.
 *
 * Used for mass seller payouts with batch processing and tracking.
 * Supports different providers and batch status tracking.
 */
final class PayoutBatch extends Model
{
    use TenantScoped;

    protected $table = 'payout_batches';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'provider',
        'status',
        'total_amount_kopecks',
        'total_count',
        'processed_count',
        'failed_count',
        'currency',
        'scheduled_at',
        'started_at',
        'completed_at',
        'failed_at',
        'provider_batch_id',
        'provider_response',
        'correlation_id',
        'metadata',
    ];

    protected $casts = [
        'total_amount_kopecks' => 'integer',
        'total_count' => 'integer',
        'processed_count' => 'integer',
        'failed_count' => 'integer',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'metadata' => 'json',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    /** @return HasMany<Payout> */
    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class, 'batch_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeScheduled($query)
    {
        return $query->where('scheduled_at', '>', now());
    }

    public function scopeReadyToProcess($query)
    {
        return $query->where('status', 'pending')
            ->where('scheduled_at', '<=', now());
    }

    public function isReadyToProcess(): bool
    {
        return $this->status === 'pending' && $this->scheduled_at->isPast();
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function getProgressPercentage(): float
    {
        if ($this->total_count === 0) {
            return 0.0;
        }

        return ($this->processed_count / $this->total_count) * 100;
    }

    public function getRemainingCount(): int
    {
        return $this->total_count - $this->processed_count - $this->failed_count;
    }

    public function getProcessedAmount(): int
    {
        // This would need to be calculated from actual payouts
        // For now, return proportional amount
        if ($this->total_count === 0) {
            return 0;
        }

        return (int) round(($this->processed_count / $this->total_count) * $this->total_amount_kopecks);
    }
}
