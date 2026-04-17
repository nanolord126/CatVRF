<?php declare(strict_types=1);

namespace App\Domains\Taxi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class TaxiTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'taxi_transactions';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'ride_id',
        'driver_id',
        'passenger_id',
        'fleet_id',
        'type',
        'amount_kopeki',
        'currency',
        'status',
        'payment_method',
        'payment_gateway',
        'gateway_transaction_id',
        'commission_kopeki',
        'driver_payout_kopeki',
        'fleet_payout_kopeki',
        'platform_payout_kopeki',
        'refunded_amount_kopeki',
        'refund_reason',
        'processed_at',
        'failed_at',
        'failure_reason',
        'correlation_id',
        'metadata',
        'tags'
    ];

    protected $casts = [
        'amount_kopeki' => 'integer',
        'commission_kopeki' => 'integer',
        'driver_payout_kopeki' => 'integer',
        'fleet_payout_kopeki' => 'integer',
        'platform_payout_kopeki' => 'integer',
        'refunded_amount_kopeki' => 'integer',
        'processed_at' => 'datetime',
        'failed_at' => 'datetime',
        'metadata' => 'json',
        'tags' => 'json',
    ];

    protected $hidden = ['metadata'];

    /**
     * Типы транзакций.
     */
    public const TYPE_PAYMENT = 'payment';
    public const TYPE_PAYOUT = 'payout';
    public const TYPE_REFUND = 'refund';
    public const TYPE_COMMISSION = 'commission';
    public const TYPE_BONUS = 'bonus';
    public const TYPE_PENALTY = 'penalty';

    /**
     * Статусы транзакций.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REFUNDED = 'refunded';

    /**
     * Методы оплаты.
     */
    public const METHOD_CARD = 'card';
    public const METHOD_CASH = 'cash';
    public const METHOD_WALLET = 'wallet';
    public const METHOD_CORPORATE = 'corporate';
    public const METHOD_SPLIT = 'split';

    protected static function booted(): void
    {
        static::creating(function (TaxiTransaction $transaction) {
            $transaction->uuid = $transaction->uuid ?? (string) Str::uuid();
            $transaction->tenant_id = $transaction->tenant_id ?? (tenant()->id ?? 1);
            $transaction->status = $transaction->status ?? self::STATUS_PENDING;
            $transaction->correlation_id = $transaction->correlation_id ?? (request()->header('X-Correlation-ID') ?? (string) Str::uuid());
        });

        static::addGlobalScope('tenant', function ($query) {
            if (tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });
    }

    /**
     * Отношения.
     */
    public function ride(): BelongsTo
    {
        return $this->belongsTo(TaxiRide::class, 'ride_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function passenger(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'passenger_id');
    }

    public function fleet(): BelongsTo
    {
        return $this->belongsTo(TaxiFleet::class, 'fleet_id');
    }

    /**
     * Получить сумму в рублях.
     */
    public function getAmountInRubles(): float
    {
        return $this->amount_kopeki / 100;
    }

    /**
     * Получить комиссию в рублях.
     */
    public function getCommissionInRubles(): float
    {
        return $this->commission_kopeki / 100;
    }

    /**
     * Получить выплату водителю в рублях.
     */
    public function getDriverPayoutInRubles(): float
    {
        return $this->driver_payout_kopeki / 100;
    }

    /**
     * Проверить, завершена ли транзакция.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Проверить, не удалась ли транзакция.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Проверить, возвращены ли деньги.
     */
    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    /**
     * Пометить как обработанную.
     */
    public function markAsProcessed(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'processed_at' => now(),
        ]);
    }

    /**
     * Пометить как неудачную.
     */
    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'failed_at' => now(),
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Пометить как возвращенную.
     */
    public function markAsRefunded(int $refundAmountKopeki, string $reason): void
    {
        $this->update([
            'status' => self::STATUS_REFUNDED,
            'refunded_amount_kopeki' => $refundAmountKopeki,
            'refund_reason' => $reason,
        ]);
    }
}
