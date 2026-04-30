<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Enums;

enum OrderPriority: int
{
    case NORMAL = 1;
    case HIGH = 2;
    case URGENT = 3;
    case VIP = 4;
    case EMERGENCY = 5;

    public function label(): string
    {
        return match ($this) {
            self::NORMAL => 'Обычный',
            self::HIGH => 'Высокий',
            self::URGENT => 'Срочный',
            self::VIP => 'VIP',
            self::EMERGENCY => 'Экстренный',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NORMAL => 'gray',
            self::HIGH => 'blue',
            self::URGENT => 'orange',
            self::VIP => 'purple',
            self::EMERGENCY => 'red',
        };
    }

    public function sortWeight(): int
    {
        return $this->value;
    }

    public function level(): int
    {
        return $this->value;
    }
}
