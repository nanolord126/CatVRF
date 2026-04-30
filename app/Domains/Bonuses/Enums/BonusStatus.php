<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Enums;

/**
 * Статусы бонусной транзакции.
 *
 * Определяет жизненный цикл бонуса от начисления до использования или истечения.
 * Соответствует требованиям CatVRF production-ready для audit trail и трассировки.
 */
enum BonusStatus: string
{
    /** Бонус находится на hold (заморожен) - ожидает разблокировки (обычно 14 дней). */
    case PENDING = 'pending';

    /** Бонус зачислен на баланс и доступен для использования. */
    case CREDITED = 'credited';

    /** Бонус потрачен пользователем. */
    case SPENT = 'spent';

    /** Бонус истёк по сроку действия (TTL). */
    case EXPIRED = 'expired';

    /** Бонус отменён (например, при возврате заказа или fraud). */
    case CANCELLED = 'cancelled';

    /** Бонус выведен в реальные деньги (только для B2B Gold/Platinum). */
    case WITHDRAWN = 'withdrawn';

    /**
     * Проверить, является ли статус активным (бонус можно использовать).
     */
    public function isActive(): bool
    {
        return $this === self::CREDITED;
    }

    /**
     * Проверить, является ли статус финальным (нет дальнейших переходов).
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::SPENT, self::EXPIRED, self::CANCELLED, self::WITHDRAWN], true);
    }

    /**
     * Получить возможные переходы из текущего статуса.
     *
     * @return array<string>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::CREDITED, self::CANCELLED],
            self::CREDITED => [self::SPENT, self::EXPIRED, self::WITHDRAWN, self::CANCELLED],
            self::SPENT, self::EXPIRED, self::CANCELLED, self::WITHDRAWN => [],
        };
    }

    /**
     * Проверить, возможен ли переход к указанному статусу.
     */
    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    /**
     * Получить человекочитаемое описание статуса (для UI и логов).
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'На ожидании',
            self::CREDITED => 'Зачислен',
            self::SPENT => 'Потрачен',
            self::EXPIRED => 'Истёк',
            self::CANCELLED => 'Отменён',
            self::WITHDRAWN => 'Выведен',
        };
    }

    /**
     * Получить цвет для UI (Filament/Blade).
     */
    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::CREDITED => 'success',
            self::SPENT => 'gray',
            self::EXPIRED => 'danger',
            self::CANCELLED => 'danger',
            self::WITHDRAWN => 'info',
        };
    }
}
