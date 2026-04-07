<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

final class BalanceTransaction extends Model
{
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

    const TYPE_DEPOSIT = 'deposit';
    const TYPE_WITHDRAWAL = 'withdrawal';
    const TYPE_COMMISSION = 'commission';
    const TYPE_BONUS = 'bonus';
    const TYPE_REFUND = 'refund';
    const TYPE_PAYOUT = 'payout';
    const TYPE_HOLD = 'hold';
    const TYPE_RELEASE = 'release';

    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

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
        static::addGlobalScope('tenant_id', function (Builder $query) {
            if (function_exists('tenant') && tenant('id')) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }
}
