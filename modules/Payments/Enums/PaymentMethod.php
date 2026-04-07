<?php declare(strict_types=1);

namespace Modules\Payments\Enums;

enum PaymentMethod: string
{
    case CARD = 'card';
    case WALLET = 'wallet';
    case BANK_TRANSFER = 'bank_transfer';
    case CASH = 'cash';
    case CRYPTO = 'crypto';
    case INVOICE = 'invoice';

    public function label(): string
    {
        return match ($this) {
            self::CARD => 'Карта',
            self::WALLET => 'Кошелек',
            self::BANK_TRANSFER => 'Банковский перевод',
            self::CASH => 'Наличные',
            self::CRYPTO => 'Криптовалюта',
            self::INVOICE => 'Инвойс',
        };
    }

    public function requiresVerification(): bool
    {
        return in_array($this, [self::BANK_TRANSFER, self::CRYPTO], true);
    }
}
