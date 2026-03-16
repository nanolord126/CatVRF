<?php

namespace App\Domains\Payments\Enums;

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
