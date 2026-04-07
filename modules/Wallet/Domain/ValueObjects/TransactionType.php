<?php

declare(strict_types=1);

namespace Modules\Wallet\Domain\ValueObjects;

/**
 * Value Object: Тип транзакции кошелька.
 */
enum TransactionType: string
{
    case DEPOSIT    = 'deposit';
    case WITHDRAWAL = 'withdrawal';
    case TRANSFER   = 'transfer';
    case HOLD       = 'hold';
    case RELEASE    = 'release';
    case COMMISSION = 'commission';
    case BONUS      = 'bonus';
    case REFUND     = 'refund';
    case PAYOUT     = 'payout';

    public function label(): string
    {
        return match ($this) {
            self::DEPOSIT    => 'Пополнение',
            self::WITHDRAWAL => 'Списание',
            self::TRANSFER   => 'Перевод',
            self::HOLD       => 'Холд',
            self::RELEASE    => 'Снятие холда',
            self::COMMISSION => 'Комиссия',
            self::BONUS      => 'Бонус',
            self::REFUND     => 'Возврат',
            self::PAYOUT     => 'Выплата',
        };
    }

    public function isCredit(): bool
    {
        return in_array($this, [self::DEPOSIT, self::RELEASE, self::BONUS, self::REFUND], true);
    }
}
