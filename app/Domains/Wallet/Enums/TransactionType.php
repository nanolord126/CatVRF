<?php

namespace App\Domains\Wallet\Enums;

enum TransactionType: string
{
    case DEPOSIT = 'deposit';
    case WITHDRAWAL = 'withdrawal';
    case TRANSFER = 'transfer';
    case PAYMENT = 'payment';
    case REFUND = 'refund';
    case BONUS = 'bonus';
    case COMMISSION = 'commission';
    case FEE = 'fee';

    public function label(): string
    {
        return match($this) {
            self::DEPOSIT => 'Пополнение',
            self::WITHDRAWAL => 'Вывод',
            self::TRANSFER => 'Трансфер',
            self::PAYMENT => 'Платеж',
            self::REFUND => 'Возврат',
            self::BONUS => 'Бонус',
            self::COMMISSION => 'Комиссия',
            self::FEE => 'Комиссия платежа',
        };
    }

    public function isDebit(): bool
    {
        return in_array($this, [self::WITHDRAWAL, self::PAYMENT, self::COMMISSION, self::FEE]);
    }

    public function isCredit(): bool
    {
        return in_array($this, [self::DEPOSIT, self::REFUND, self::BONUS, self::TRANSFER]);
    }
}
