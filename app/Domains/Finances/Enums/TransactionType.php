<?php

declare(strict_types=1);

namespace App\Domains\Finances\Enums;

/**
 * Тип финансовой транзакции.
 *
 * Каждая транзакция через WalletService имеет строго один тип.
 * Типы соответствуют таблице balance_transactions.type.
 *
 * CANON CatVRF 2026 — Layer Enums (плоская директория).
 *
 * @package App\Domains\Finances\Enums
 */
enum TransactionType: string
{
    case DEPOSIT = 'deposit';
    case WITHDRAWAL = 'withdrawal';
    case COMMISSION = 'commission';
    case BONUS = 'bonus';
    case REFUND = 'refund';
    case PAYOUT = 'payout';
    case HOLD = 'hold';
    case RELEASE_HOLD = 'release_hold';

    /**
     * Является ли тип приходной операцией (увеличивает баланс).
     */
    public function isCredit(): bool
    {
        return in_array($this, [
            self::DEPOSIT,
            self::BONUS,
            self::REFUND,
            self::RELEASE_HOLD,
        ], true);
    }

    /**
     * Является ли тип расходной операцией (уменьшает баланс).
     */
    public function isDebit(): bool
    {
        return in_array($this, [
            self::WITHDRAWAL,
            self::COMMISSION,
            self::PAYOUT,
            self::HOLD,
        ], true);
    }

    /**
     * Человекочитаемая метка для Filament UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::DEPOSIT      => 'Пополнение',
            self::WITHDRAWAL   => 'Списание',
            self::COMMISSION   => 'Комиссия',
            self::BONUS        => 'Бонус',
            self::REFUND       => 'Возврат',
            self::PAYOUT       => 'Выплата',
            self::HOLD         => 'Холд',
            self::RELEASE_HOLD => 'Снятие холда',
        };
    }

    /**
     * Цвет badge для Filament.
     */
    public function color(): string
    {
        return match ($this) {
            self::DEPOSIT, self::BONUS, self::REFUND, self::RELEASE_HOLD => 'success',
            self::WITHDRAWAL, self::COMMISSION, self::PAYOUT, self::HOLD => 'danger',
        };
    }
}
