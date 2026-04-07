<?php declare(strict_types=1);

namespace App\Domains\Finances\Domain\Enums;

/**
 * Статус выплаты (Payout) в финансовой вертикали CatVRF 2026.
 *
 * Жизненный цикл:
 *   DRAFT → PENDING → PROCESSING → COMPLETED
 *                  ↘ FAILED
 *                  ↘ CANCELLED
 *
 * @package App\Domains\Finances\Domain\Enums
 */
enum PayoutStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    /**
     * Терминальные статусы — переход из них невозможен.
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::COMPLETED, self::FAILED, self::CANCELLED], true);
    }

    /**
     * Проверка допустимости перехода из текущего в целевой статус.
     */
    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::DRAFT      => in_array($target, [self::PENDING, self::CANCELLED], true),
            self::PENDING    => in_array($target, [self::PROCESSING, self::CANCELLED, self::FAILED], true),
            self::PROCESSING => in_array($target, [self::COMPLETED, self::FAILED], true),
            self::COMPLETED,
            self::FAILED,
            self::CANCELLED  => false,
        };
    }

    /**
     * Человекочитаемая метка для Filament UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT      => 'Черновик',
            self::PENDING    => 'Ожидает обработки',
            self::PROCESSING => 'В обработке',
            self::COMPLETED  => 'Завершена',
            self::FAILED     => 'Ошибка',
            self::CANCELLED  => 'Отменена',
        };
    }

    /**
     * Цвет badge для Filament.
     */
    public function color(): string
    {
        return match ($this) {
            self::DRAFT      => 'gray',
            self::PENDING    => 'warning',
            self::PROCESSING => 'info',
            self::COMPLETED  => 'success',
            self::FAILED     => 'danger',
            self::CANCELLED  => 'gray',
        };
    }
}
