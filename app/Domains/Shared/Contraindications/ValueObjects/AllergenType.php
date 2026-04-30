<?php

declare(strict_types=1);

namespace App\Domains\Shared\Contraindications\ValueObjects;

enum AllergenType: string
{
    case CONTACT_DERMATITIS = 'contact_dermatitis';
    case RESPIRATORY = 'respiratory';
    case FOOD = 'food';
    case CHEMICAL = 'chemical';
    case METAL = 'metal';
    case TEXTILE = 'textile';
    case RUBBER = 'rubber';
    case LATEX = 'latex';

    public function getLabel(): string
    {
        return match ($this) {
            self::CONTACT_DERMATITIS => 'Контактный дерматит',
            self::RESPIRATORY => 'Дыхательная аллергия',
            self::FOOD => 'Пищевая аллергия',
            self::CHEMICAL => 'Химическая аллергия',
            self::METAL => 'Металл-аллергия',
            self::TEXTILE => 'Текстильная аллергия',
            self::RUBBER => 'Аллергия на резину',
            self::LATEX => 'Аллергия на латекс',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::CONTACT_DERMATITIS => 'red',
            self::RESPIRATORY => 'orange',
            self::FOOD => 'yellow',
            self::CHEMICAL => 'purple',
            self::METAL => 'blue',
            self::TEXTILE => 'green',
            self::RUBBER => 'teal',
            self::LATEX => 'pink',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::CONTACT_DERMATITIS => 'heroicon-o-exclamation-triangle',
            self::RESPIRATORY => 'heroicon-o-lungs',
            self::FOOD => 'heroicon-o-no-symbol',
            self::CHEMICAL => 'heroicon-o-flask',
            self::METAL => 'heroicon-o-cube',
            self::TEXTILE => 'heroicon-o-sparkles',
            self::RUBBER => 'heroicon-o-circle',
            self::LATEX => 'heroicon-o-shield-exclamation',
        };
    }

    public function getCommonTriggers(): array
    {
        return match ($this) {
            self::CONTACT_DERMATITIS => ['nickel', 'chromium', 'cobalt', 'formaldehyde', 'fragrance'],
            self::RESPIRATORY => ['dust', 'pollen', 'mold', 'latex particles'],
            self::FOOD => ['gluten', 'dairy', 'nuts', 'soy'],
            self::CHEMICAL => ['dyes', 'bleach', 'solvents', 'adhesives'],
            self::METAL => ['nickel', 'chromium', 'cobalt', 'copper'],
            self::TEXTILE => ['wool', 'cotton', 'polyester', 'acrylic'],
            self::RUBBER => ['natural rubber', 'synthetic rubber', 'neoprene'],
            self::LATEX => ['natural latex', 'latex proteins'],
        };
    }
}
