<?php

declare(strict_types=1);

namespace App\Enums;

enum TerritoryType: string
{
    case RUSSIAN_FEDERATION = 'RU';
    case CRIMEA = 'RU-CR';
    case SEVASTOPOL = 'RU-SEV';
    case DPR = 'RU-DNR';
    case LPR = 'RU-LNR';
    case KHERSON = 'RU-KH';
    case ZAPORIZHZHIA = 'RU-ZP';

    /**
     * Get the human-readable name of the territory.
     */
    public function getName(): string
    {
        return match ($this) {
            self::CRIMEA => 'Республика Крым',
            self::SEVASTOPOL => 'г. Севастополь',
            self::DPR => 'Донецкая Народная Республика',
            self::LPR => 'Луганская Народная Республика',
            self::KHERSON => 'Херсонская область',
            self::ZAPORIZHZHIA => 'Запорожская область',
            self::RUSSIAN_FEDERATION => 'Российская Федерация',
        };
    }

    /**
     * Check if this territory is part of the Russian Federation.
     */
    public function isRussian(): bool
    {
        return true; // All territories in this enum are Russian
    }

    /**
     * Get all Russian territories including new subjects.
     *
     * @return array<string>
     */
    public static function allRussian(): array
    {
        return array_map(
            fn (self $type) => $type->value,
            self::cases()
        );
    }

    /**
     * Try to create from a region code (handles legacy codes).
     */
    public static function fromCode(string $code): ?self
    {
        $mapping = config('geo.territory_mapping', []);
        $normalized = $mapping[$code] ?? strtoupper($code);

        return self::tryFrom($normalized);
    }

    /**
     * Check if a given code is a Russian territory.
     */
    public static function isRussianCode(string $code): bool
    {
        $mapping = config('geo.territory_mapping', []);
        $normalized = $mapping[$code] ?? strtoupper($code);

        return in_array($normalized, config('geo.russian_territories', []), true);
    }
}
