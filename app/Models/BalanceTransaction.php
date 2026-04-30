<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

final class BalanceTransaction extends Model
{
    public const TYPE_DEPOSIT = 'deposit';

    public const TYPE_WITHDRAWAL = 'withdrawal';

    public const TYPE_COMMISSION = 'commission';

    public const TYPE_BONUS = 'bonus';

    public const TYPE_REFUND = 'refund';

    public const TYPE_PAYOUT = 'payout';

    public const TYPE_HOLD = 'hold';

    public const TYPE_RELEASE = 'release';

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'balance_transactions';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'wallet_id',
        'tenant_id',
        'type',
        'amount',
        'status',
        'reason',
        'source_type',
        'source_id',
        'correlation_id',
        'balance_before',
        'balance_after',
        'tags',
    ];

    protected $casts = [
        'amount' => 'integer',
        'balance_before' => 'integer',
        'balance_after' => 'integer',
        'tags' => 'json',
    ];

    /**
     * Связь с кошельком
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id', 'id');
    }

    /**
     * Скоп для завершённых транзакций
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Скоп для незавершённых транзакций
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    protected static function booted(): void
    {
        parent::booted();
        self::addGlobalScope('tenant_id', function (Builder $query) {
            if (function_exists('tenant') && tenant('id')) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }
}
