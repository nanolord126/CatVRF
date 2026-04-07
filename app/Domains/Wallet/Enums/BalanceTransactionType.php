<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Enums;

/**
 * Строгий перечень типов балансовых транзакций.
 *
 * Канон CatVRF 2026: deposit, withdrawal, commission, bonus, refund, payout, hold, release_hold.
 * Каждый case обязан иметь label(), color(), isCredit(), isDebit().
 */
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

    /** Человекочитаемая метка. */
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

    /** Цвет для UI (Filament badge). */
    public function color(): string
    {
        return match ($this) {
            self::DEPOSIT, self::BONUS, self::REFUND, self::RELEASE_HOLD => 'success',
            self::WITHDRAWAL, self::COMMISSION, self::PAYOUT, self::HOLD => 'danger',
        };
    }

    /** Является ли зачислением (увеличивает доступный баланс). */
    public function isCredit(): bool
    {
        return in_array($this, [self::DEPOSIT, self::BONUS, self::REFUND, self::RELEASE_HOLD], true);
    }

    /** Является ли списанием (уменьшает доступный баланс). */
    public function isDebit(): bool
    {
        return ! $this->isCredit();
    }
}
