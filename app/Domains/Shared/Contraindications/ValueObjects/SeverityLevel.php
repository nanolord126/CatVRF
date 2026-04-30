<?php

declare(strict_types=1);

namespace App\Domains\Shared\Contraindications\ValueObjects;

enum SeverityLevel: string
{
    case MILD = 'mild';
    case MODERATE = 'moderate';
    case SEVERE = 'severe';
    case LIFE_THREATENING = 'life_threatening';

    public function getLabel(): string
    {
        return match ($this) {
            self::MILD => 'Лёгкая',
            self::MODERATE => 'Умеренная',
            self::SEVERE => 'Тяжёлая',
            self::LIFE_THREATENING => 'Угрожающая жизни',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::MILD => 'green',
            self::MODERATE => 'yellow',
            self::SEVERE => 'orange',
            self::LIFE_THREATENING => 'red',
        };
    }

    public function getPriority(): int
    {
        return match ($this) {
            self::MILD => 1,
            self::MODERATE => 2,
            self::SEVERE => 3,
            self::LIFE_THREATENING => 4,
        };
    }

    public function requiresWarning(): bool
    {
        return $this === self::MODERATE || $this === self::SEVERE;
    }

    public function requiresBlock(): bool
    {
        return $this === self::SEVERE || $this === self::LIFE_THREATENING;
    }

    public function getActionMessage(): string
    {
        return match ($this) {
            self::MILD => 'Рекомендуется осторожность',
            self::MODERATE => 'Предупреждение: возможна реакция',
            self::SEVERE => 'ВНИМАНИЕ: высокая вероятность реакции',
            self::LIFE_THREATENING => 'ОПАСНО: продукт заблокирован',
        };
    }
}
