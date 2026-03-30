<?php declare(strict_types=1);

namespace Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WalletTransaction extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
