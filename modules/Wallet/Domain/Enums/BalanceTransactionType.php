<?php

declare(strict_types=1);

namespace Modules\Wallet\Domain\Enums;

enum BalanceTransactionType: string
{
    case DEPOSIT = 'deposit';
    case WITHDRAWAL = 'withdrawal';
    case COMMISSION = 'commission';
    case BONUS = 'bonus';
    case REFUND = 'refund';
    case PAYOUT = 'payout';
    case HOLD = 'hold';
    case RELEASE_HOLD = 'release_hold';

    public function label(): string
    {
        return match ($this) {
            self::DEPOSIT => 'Пополнение',
            self::WITHDRAWAL => 'Списание',
            self::COMMISSION => 'Комиссия',
            self::BONUS => 'Бонус',
            self::REFUND => 'Возврат',
            self::PAYOUT => 'Выплата',
            self::HOLD => 'Холд (заморозка)',
            self::RELEASE_HOLD => 'Разморозка',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DEPOSIT, self::BONUS, self::REFUND, self::RELEASE_HOLD => 'success',
            self::WITHDRAWAL, self::COMMISSION, self::PAYOUT, self::HOLD => 'danger',
        };
    }

    public function isCredit(): bool
    {
        return in_array($this, [self::DEPOSIT, self::BONUS, self::REFUND, self::RELEASE_HOLD], true);
    }

    public function isDebit(): bool
    {
        return !$this->isCredit();
    }
}
