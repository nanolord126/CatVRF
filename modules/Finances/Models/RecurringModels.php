<?php declare(strict_types=1);

namespace Modules\Finances\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WalletCard extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    HasEcosystemFeatures, HasEcosystemAuth};
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
    use Illuminate\Support\{Carbon, Facades};
    use Illuminate\Support\Facades\Log;
    use Throwable;
    
    /**
     * Модель для сохранённых карт (токенизированные платёжные реквизиты).
     * 
     * @property int $id
     * @property int $user_id
     * @property int $tenant_id
     * @property string $token
     * @property string $card_last_four
     * @property string $card_brand
     * @property int $exp_month
     * @property int $exp_year
     * @property bool $is_active
     * @property bool $is_default
     * @property string|null $correlation_id
     * @property \Illuminate\Support\Carbon $created_at
     * @property \Illuminate\Support\Carbon $updated_at
     */
    class WalletCard extends Model
    {
        use HasEcosystemFeatures, HasEcosystemAuth;
    
        protected $table = 'wallet_cards';
        protected $guarded = [];
        protected $casts = [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    
        protected $fillable = [
            'user_id',
            'tenant_id',
            'token',
            'card_last_four',
            'card_brand',
            'exp_month',
            'exp_year',
            'is_active',
            'is_default',
            'correlation_id',
        ];
    
        /**
         * Связь с пользователем.
         */
        public function user(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class);
        }
    
        /**
         * Подписки, привязанные к этой карте.
         */
        public function subscriptions(): HasMany
        {
            return $this->hasMany(Subscription::class);
        }
    
        /**
         * Проверить, заканчивается ли действие карты.
         */
        public function isExpired(): bool
        {
            return (int) $this->exp_year < Carbon::now()->year ||
                   ((int) $this->exp_year === Carbon::now()->year && (int) $this->exp_month < Carbon::now()->month);
        }
    }
    
    /**
     * Модель повторяющихся подписок (автоплатежи).
     * 
     * @property int $id
     * @property int $user_id
     * @property int $tenant_id
     * @property int $wallet_card_id
     * @property float $amount
     * @property string $frequency
     * @property string $status
     * @property \Illuminate\Support\Carbon $starts_at
     * @property \Illuminate\Support\Carbon|null $ends_at
     * @property \Illuminate\Support\Carbon|null $last_payment_at
     * @property \Illuminate\Support\Carbon $next_payment_at
     * @property string|null $correlation_id
     * @property array $metadata
     * @property \Illuminate\Support\Carbon $created_at
     * @property \Illuminate\Support\Carbon $updated_at
     */
    class Subscription extends Model
    {
        use HasEcosystemFeatures, HasEcosystemAuth;
    
        protected $table = 'subscriptions';
        protected $guarded = [];
        protected $casts = [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'last_payment_at' => 'datetime',
            'amount' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    
        protected $fillable = [
            'user_id',
            'tenant_id',
            'wallet_card_id',
            'amount',
            'frequency',
            'status',
            'starts_at',
            'ends_at',
            'last_payment_at',
            'next_payment_at',
            'correlation_id',
            'metadata',
        ];
    
        // Статусы подписки
        public const STATUS_ACTIVE = 'active';
        public const STATUS_PAUSED = 'paused';
        public const STATUS_CANCELLED = 'cancelled';
        public const STATUS_FAILED = 'failed';
    
        // Периодичность
        public const FREQUENCY_DAILY = 'daily';
        public const FREQUENCY_WEEKLY = 'weekly';
        public const FREQUENCY_MONTHLY = 'monthly';
        public const FREQUENCY_YEARLY = 'yearly';
    
        /**
         * Связь с пользователем.
         */
        public function user(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class);
        }
    
        /**
         * Связь с картой.
         */
        public function card(): BelongsTo
        {
            return $this->belongsTo(WalletCard::class, 'wallet_card_id');
        }
    
        /**
         * Проверить, активна ли подписка.
         */
        public function isActive(): bool
        {
            return $this->status === self::STATUS_ACTIVE &&
                   $this->starts_at <= Carbon::now() &&
                   ($this->ends_at === null || $this->ends_at > Carbon::now());
        }
    
        /**
         * Получить следующую дату платежа.
         */
        public function getNextPaymentDate(): \DateTime
        {
            $date = $this->last_payment_at ? clone $this->last_payment_at : clone $this->starts_at;
    
            return match ($this->frequency) {
                self::FREQUENCY_DAILY => $date->addDay(),
                self::FREQUENCY_WEEKLY => $date->addWeek(),
                self::FREQUENCY_MONTHLY => $date->addMonth(),
                self::FREQUENCY_YEARLY => $date->addYear(),
                default => $date,
            };
        }
    
        /**
         * Отменить подписку.
         */
        public function cancel(string $reason = null): bool
        {
            try {
                $this->update([
                    'status' => self::STATUS_CANCELLED,
                    'ends_at' => Carbon::now(),
                ]);
    
                Log::channel('payments')->info('Subscription cancelled', [
                    'subscription_id' => $this->id,
                    'user_id' => $this->user_id,
                    'reason' => $reason,
                    'correlation_id' => $this->correlation_id,
                ]);
    
                return true;
            } catch (Throwable $e) {
                Log::error('Failed to cancel subscription', [
                    'subscription_id' => $this->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }
}
