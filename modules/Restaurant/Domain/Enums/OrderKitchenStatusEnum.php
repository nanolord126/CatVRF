<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Enums;

enum OrderKitchenStatusEnum: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case READY = 'ready';
    case SERVED = 'served';
    case CANCELLED = 'cancelled';
    case PROBLEM = 'problem';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Ожидает',
            self::IN_PROGRESS => 'В работе',
            self::READY => 'Готово',
            self::SERVED => 'Выдано',
            self::CANCELLED => 'Отменено',
            self::PROBLEM => 'Проблема',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::IN_PROGRESS => 'blue',
            self::READY => 'green',
            self::SERVED => 'emerald',
            self::CANCELLED => 'red',
            self::PROBLEM => 'orange',
        };
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::PENDING, self::IN_PROGRESS], true);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::READY, self::SERVED, self::CANCELLED], true);
    }
}
