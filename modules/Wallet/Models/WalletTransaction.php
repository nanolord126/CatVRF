<?php

declare(strict_types=1);

namespace Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Транзакция кошелька - логирование всех операций с денежными средствами.
 * Согласно КАНОН 2026: корреляционный ID, tenant scoping, audit логирование, типы операций.
 *
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int|null $wallet_id
 * @property int|null $user_id
 * @property string $type (deposit, withdrawal, commission, bonus, refund, payout, hold, release)
 * @property int $amount Сумма в копейках
 * @property string $status (pending, completed, failed, cancelled)
 * @property string $currency (RUB по умолчанию)
 * @property string|null $source_type (order, appointment, manual, import, refund, etc.)
 * @property int|null $source_id ID источника операции
 * @property string|null $correlation_id Корреляционный ID
 * @property array|null $tags Теги для аналитики
 * @property array|null $metadata Дополнительные метаданные
 * @property string|null $description Описание операции
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
final class WalletTransaction extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'wallet_transactions';

    protected $fillable = [
        'tenant_id',
        'wallet_id',
        'user_id',
        'type',
        'amount',
        'status',
        'currency',
        'source_type',
        'source_id',
        'correlation_id',
        'tags',
        'metadata',
        'description',
    ];

    protected $casts = [
        'amount' => 'integer',
        'source_id' => 'integer',
        'tags' => AsCollection::class,
        'metadata' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = ['deleted_at'];

    /**
     * Типы операций.
     */
    public const string TYPE_DEPOSIT = 'deposit';
    public const string TYPE_WITHDRAWAL = 'withdrawal';
    public const string TYPE_COMMISSION = 'commission';
    public const string TYPE_BONUS = 'bonus';
    public const string TYPE_REFUND = 'refund';
    public const string TYPE_PAYOUT = 'payout';
    public const string TYPE_HOLD = 'hold';
    public const string TYPE_RELEASE = 'release';

    /**
     * Статусы операций.
     */
    public const string STATUS_PENDING = 'pending';
    public const string STATUS_COMPLETED = 'completed';
    public const string STATUS_FAILED = 'failed';
    public const string STATUS_CANCELLED = 'cancelled';

    /**
     * Globakl scope для tenant scoping.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant_scoped', function ($query) {
            if ($tenantId = tenant('id')) {
                $query->where('tenant_id', $tenantId);
            }
        });
    }

    /**
     * Получить кошелёк, к которому относится транзакция.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(\Modules\Wallet\Models\Wallet::class);
    }

    /**
     * Получить сумму в рублях.
     */
    public function getAmountInRubles(): float
    {
        return $this->amount / 100;
    }

    /**
     * Установить сумму в рублях.
     */
    public function setAmountInRubles(float $rubles): void
    {
        $this->amount = (int) ($rubles * 100);
    }

    /**
     * Проверить, является ли операция пополнением.
     */
    public function isDeposit(): bool
    {
        return $this->type === self::TYPE_DEPOSIT;
    }

    /**
     * Проверить, является ли операция снятием.
     */
    public function isWithdrawal(): bool
    {
        return $this->type === self::TYPE_WITHDRAWAL;
    }

    /**
     * Проверить, завершена ли операция.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Проверить, отменена ли операция.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Проверить, не завершена ли операция.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
