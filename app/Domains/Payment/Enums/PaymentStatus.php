<?php

declare(strict_types=1);

namespace App\Domains\Payment\Enums;

/**
 * Статусы платёжных операций.
 *
 * Жизненный цикл: PENDING → AUTHORIZED → CAPTURED → (REFUNDED)
 *                  PENDING → FAILED
 *                  AUTHORIZED → CANCELLED
 */
enum PaymentStatus: string
{
    case PENDING = 'pending';
    case AUTHORIZED = 'authorized';
    case CAPTURED = 'captured';
    case REFUNDED = 'refunded';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    /**
     * Человекочитаемая метка для UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Ожидает',
            self::AUTHORIZED => 'Авторизован',
            self::CAPTURED => 'Проведён',
            self::REFUNDED => 'Возврат',
            self::FAILED => 'Ошибка',
            self::CANCELLED => 'Отменён',
        };
    }

    /**
     * Цвет для Filament badge.
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::AUTHORIZED => 'info',
            self::CAPTURED => 'success',
            self::REFUNDED => 'gray',
            self::FAILED => 'danger',
            self::CANCELLED => 'gray',
        };
    }

    /**
     * Является ли статус финальным (нельзя изменить).
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::CAPTURED,
            self::REFUNDED,
            self::FAILED,
            self::CANCELLED,
        ], true);
    }

    /**
     * Разрешённые переходы.
     *
     * @return array<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::AUTHORIZED, self::FAILED, self::CANCELLED],
            self::AUTHORIZED => [self::CAPTURED, self::CANCELLED],
            self::CAPTURED => [self::REFUNDED],
            self::REFUNDED, self::FAILED, self::CANCELLED => [],
        };
    }

    /**
     * Можно ли перейти в указанный статус.
     */
    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
