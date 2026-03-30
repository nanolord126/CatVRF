<?php declare(strict_types=1);

namespace Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Wallet extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;
    
        protected $table = 'wallets';
    
        protected $fillable = [
            'tenant_id',
            'business_group_id',
            'user_id',
            'owner_id',
            'owner_type',
            'current_balance',
            'held_amount',
            'currency',
            'uuid',
            'correlation_id',
            'tags',
        ];
    
        protected $casts = [
            'current_balance' => 'integer',
            'held_amount' => 'integer',
            'tags' => 'json',
        ];
    
        protected $hidden = [];
    
        /**
         * Получить владельца кошелька (morphable relation).
         */
        public function owner(): MorphTo
        {
            return $this->morphTo();
        }
    
        /**
         * Получить все транзакции кошелька.
         */
        public function transactions(): HasMany
        {
            return $this->hasMany(\Modules\Wallet\Models\WalletTransaction::class, 'wallet_id');
        }
    
        /**
         * Получить доступный баланс (текущий - зарезервированный).
         */
        public function getAvailableBalance(): int
        {
            return $this->current_balance - $this->held_amount;
        }
    
        /**
         * Получить процент использования баланса.
         */
        public function getUsagePercentage(): float
        {
            if ($this->current_balance <= 0) {
                return 0.0;
            }
    
            return ($this->held_amount / $this->current_balance) * 100.0;
        }
}
