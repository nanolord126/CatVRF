<?php declare(strict_types=1);

namespace Modules\Finances\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PaymentTransaction extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    HasEcosystemFeatures, HasEcosystemAuth};
    use Illuminate\Database\Eloquent\Builder;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;
    use Illuminate\Database\Eloquent\SoftDeletes;
    
    /**
     * Модель платежной транзакции.
     * Хранит информацию о всех платежах (card, SBP, wallet, installments).
     * Согласно КАНОН 2026: Payment / Finance / Wallet / Balance / Bonus.
     */
    final class PaymentTransaction extends Model
    {
        use HasEcosystemFeatures, HasEcosystemAuth, SoftDeletes;
    
        protected $table = 'payment_transactions';
    
        // Статусы платежа (согласно КАНОН)
        public const STATUS_PENDING = 'pending';
        public const STATUS_AUTHORIZED = 'authorized';
        public const STATUS_CAPTURED = 'captured';
        public const STATUS_REFUNDED = 'refunded';
        public const STATUS_FAILED = 'failed';
        public const STATUS_CANCELLED = 'cancelled';
        public const STATUS_EXPIRED = 'expired';
    
        // Статусы фискализации (54-ФЗ)
        public const FISCAL_NOT_SENT = 'not_sent';
        public const FISCAL_PENDING = 'pending';
        public const FISCAL_REGISTERED = 'registered';
        public const FISCAL_ERROR = 'error';
        public const FISCAL_REFUNDED = 'refunded';
    
        protected $fillable = [
            'uuid',
            'correlation_id',
            'tenant_id',
            'user_id',
            'business_group_id',
            'idempotency_key',
            'payload_hash',
            'provider_payment_id',
            'payment_method',
            'amount',
            'hold_amount',
            'is_hold',
            'is_captured',
            'status',
            'failure_reason',
            'failure_code',
            'fiscal_document_id',
            'fiscal_status',
            'fiscal_metadata',
            'metadata',
            'tags',
            'description',
            'authorized_at',
            'captured_at',
            'refunded_at',
        ];
    
        protected $casts = [
            'amount' => 'decimal:2',
            'hold_amount' => 'decimal:2',
            'is_hold' => 'boolean',
            'is_captured' => 'boolean',
            'metadata' => 'json',
            'fiscal_metadata' => 'json',
            'tags' => 'json',
            'authorized_at' => 'datetime',
            'captured_at' => 'datetime',
            'refunded_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    
        protected $hidden = [
            'payload_hash',
        ];
    
        /**
         * Boot метод с глобальным scope для tenant и business_group.
         */
        protected static function booted(): void
        {
            // Global scope: фильтр по tenant_id
            static::addGlobalScope('tenant', function (Builder $query) {
                if (auth()->check() && auth()->user()->tenant_id) {
                    $query->where('tenant_id', auth()->user()->tenant_id);
                }
            });
        }
    
        /**
         * Связь с пользователем.
         */
        public function user(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class);
        }
    
        /**
         * Scope: фильтр по tenant_id.
         */
        public function scopeTenant(Builder $query, string $tenantId): Builder
        {
            return $query->where('tenant_id', $tenantId)->withoutGlobalScope('tenant');
        }
    
        /**
         * Scope: фильтр по correlation_id.
         */
        public function scopeByCorrelation(Builder $query, string $correlationId): Builder
        {
            return $query->where('correlation_id', $correlationId);
        }
    
        /**
         * Scope: фильтр по business_group_id.
         */
        public function scopeByBusinessGroup(Builder $query, string $businessGroupId): Builder
        {
            return $query->where('business_group_id', $businessGroupId);
        }
    
        /**
         * Scope: успешные платежи.
         */
        public function scopeSuccessful(Builder $query): Builder
        {
            return $query->whereIn('status', [self::STATUS_CAPTURED, self::STATUS_AUTHORIZED]);
        }
    
        /**
         * Scope: ожидающие платежи.
         */
        public function scopePending(Builder $query): Builder
        {
            return $query->where('status', self::STATUS_PENDING);
        }
    
        /**
         * Определить, является ли платёж успешным.
         */
        public function isSuccessful(): bool
        {
            return in_array($this->status, [self::STATUS_CAPTURED, self::STATUS_AUTHORIZED]);
        }
    
        /**
         * Определить, является ли платёж холдом.
         */
        public function isHold(): bool
        {
            return $this->is_hold === true && $this->status === self::STATUS_AUTHORIZED;
        }
    
        /**
         * Определить, был ли платёж захвачен.
         */
        public function isCaptured(): bool
        {
            return $this->is_captured === true && $this->status === self::STATUS_CAPTURED;
        }
    
        /**
         * Обновить статус с логированием.
         *
         * @throws \InvalidArgumentException
         */
        public function updateStatus(string $newStatus, array $metadata = []): bool
        {
            $validStatuses = [
                self::STATUS_PENDING,
                self::STATUS_AUTHORIZED,
                self::STATUS_CAPTURED,
                self::STATUS_REFUNDED,
                self::STATUS_FAILED,
                self::STATUS_CANCELLED,
                self::STATUS_EXPIRED,
            ];
    
            if (! in_array($newStatus, $validStatuses)) {
                throw new \InvalidArgumentException("Invalid payment status: {$newStatus}");
            }
    
            $this->status = $newStatus;
    
            if (! empty($metadata)) {
                $this->metadata = array_merge($this->metadata ?? [], $metadata);
            }
    
            return $this->save();
        }
    
        /**
         * Отметить платёж как захваченный.
         */
        public function markCaptured(?string $correlationId = null): bool
        {
            return $this->updateStatus(self::STATUS_CAPTURED, [
                'captured_correlation_id' => $correlationId ?? \Illuminate\Support\Str::uuid(),
            ]);
        }
    
        /**
         * Отметить платёж как возвращённый.
         */
        public function markRefunded(?string $correlationId = null): bool
        {
            return $this->updateStatus(self::STATUS_REFUNDED, [
                'refunded_correlation_id' => $correlationId ?? \Illuminate\Support\Str::uuid(),
            ]);
        }
    
        /**
         * Отметить платёж как ошибку.
         */
        public function markFailed(string $reason, string $code = ''): bool
        {
            $this->failure_reason = $reason;
            $this->failure_code = $code;
    
            return $this->updateStatus(self::STATUS_FAILED);
        }
}
