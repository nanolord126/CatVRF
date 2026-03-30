<?php declare(strict_types=1);

namespace Modules\Payments\Enums;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PaymentMethod extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    case CARD = 'card';
        case WALLET = 'wallet';
        case BANK_TRANSFER = 'bank_transfer';
        case CASH = 'cash';
        case CRYPTO = 'crypto';
        case INVOICE = 'invoice';
    
        public function label(): string
        {
            return match($this) {
                self::CARD => 'Карта',
                self::WALLET => 'Кошелек',
                self::BANK_TRANSFER => 'Банковский перевод',
                self::CASH => 'Наличные',
                self::CRYPTO => 'Криптовалюта',
                self::INVOICE => 'Инвойс',
            };
        }
    
        public function icon(): string
        {
            return match($this) {
                self::CARD => 'credit-card',
                self::WALLET => 'wallet',
                self::BANK_TRANSFER => 'bank',
                self::CASH => 'money-bill',
                self::CRYPTO => 'bitcoin',
                self::INVOICE => 'file-invoice',
            };
        }
    
        public function requiresVerification(): bool
        {
            return in_array($this, [self::BANK_TRANSFER, self::CRYPTO]);
        }
}
