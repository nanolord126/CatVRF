<?php

declare(strict_types=1);

namespace App\Domains\Shared\Size\ValueObjects;

enum Region: string
{
    case EU = 'eu';
    case US = 'us';
    case UK = 'uk';
    case RU = 'ru';
    case ASIA = 'asia';
    case INTERNATIONAL = 'international';

    public function getLabel(): string
    {
        return match ($this) {
            self::EU => 'Европа',
            self::US => 'США',
            self::UK => 'Великобритания',
            self::RU => 'Россия',
            self::ASIA => 'Азия',
            self::INTERNATIONAL => 'Международный',
        };
    }

    public function getFlag(): string
    {
        return match ($this) {
            self::EU => '🇪🇺',
            self::US => '🇺🇸',
            self::UK => '🇬🇧',
            self::RU => '🇷🇺',
            self::ASIA => '🌏',
            self::INTERNATIONAL => '🌍',
        };
    }
}
