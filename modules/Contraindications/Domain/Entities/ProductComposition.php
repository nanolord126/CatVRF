<?php

declare(strict_types=1);

namespace Modules\Contraindications\Domain\Entities;

final readonly class ProductComposition
{
    /**
     * @param array<int> $ingredientIds
     * @param array<string> $allergens
     */
    public function __construct(
        public int $id,
        public int $tenantId,
        public string $composableType,
        public int $composableId,
        public array $ingredientIds,
        public ?float $caloriesPer100g,
        public ?float $proteins,
        public ?float $fats,
        public ?float $carbs,
        public array $allergens,
    ) {
    }

    public function containsAllergen(string $allergenName): bool
    {
        return in_array(strtolower($allergenName), array_map('strtolower', $this->allergens), true);
    }
}
