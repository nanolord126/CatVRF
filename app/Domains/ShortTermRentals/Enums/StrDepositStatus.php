<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Enums;

/**
 * КАНОН 2026: Статусы залога (Deposit)
 */
enum StrDepositStatus: string
{
    case PENDING = 'pending';
    case HELD = 'held';
    case RELEASED = 'released';
    case CHARGED = 'charged';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Ожидает холда',
            self::HELD => 'Вхолдирован на карте гостя',
            self::RELEASED => 'Возвращен гостю',
            self::CHARGED => 'Списан в счет ущерба',
        };
    }
}
