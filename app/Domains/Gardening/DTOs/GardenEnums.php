<?php

declare(strict_types=1);

namespace App\Domains\Gardening\DTOs;

/**
     * Enums representing Gardening constraints (Alternative to strictly backed enums)
     */
final readonly class GardenEnums
{
        public const HARDINESS_ZONES = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11'];
        public const LIGHT_REQ = ['full_sun', 'partial_shade', 'shade'];
        public const WATER_NEEDS = ['low', 'medium', 'high'];
        public const BOX_FREQUENCIES = ['monthly', 'quarterly', 'seasonal'];

        public const GARDEN_VERTICAL_CODE = 'GARDEN_2026';
}
