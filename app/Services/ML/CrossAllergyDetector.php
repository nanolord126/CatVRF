<?php

declare(strict_types=1);

namespace App\Services\ML;

final class CrossAllergyDetector
{
    /**
     * Known cross-allergy relationships
     * Key: allergen, Value: array of potential cross-allergens
     */
    private const CROSS_ALLERGY_MAP = [
        'chicken' => ['egg', 'turkey', 'duck', 'feathers'],
        'beef' => ['lamb', 'pork', 'milk'],
        'dairy' => ['casein', 'whey', 'lactose'],
        'wheat' => ['gluten', 'barley', 'rye', 'oats'],
        'soy' => ['legumes', 'peanuts', 'beans'],
        'peanuts' => ['tree nuts', 'soy', 'legumes'],
        'tree nuts' => ['peanuts', 'seeds'],
        'latex' => ['banana', 'avocado', 'kiwi', 'chestnut'],
        'pollen' => ['certain fruits', 'vegetables'],
        'dust mites' => ['shrimp', 'cockroach'],
    ];

    public function detect(array $features): array
    {
        $existingAllergies = $features['existing_allergies'] ?? [];
        $productIngredients = $features['product_ingredients'] ?? [];

        $crossRisks = [];

        foreach ($existingAllergies as $allergy) {
            $allergyLower = strtolower($allergy);

            if (isset(self::CROSS_ALLERGY_MAP[$allergyLower])) {
                $potentialCrossAllergens = self::CROSS_ALLERGY_MAP[$allergyLower];

                foreach ($potentialCrossAllergens as $crossAllergen) {
                    // Check if product contains cross-allergen
                    if ($this->containsIngredient($productIngredients, $crossAllergen)) {
                        $crossRisks[] = [
                            'source_allergy' => $allergy,
                            'cross_allergen' => $crossAllergen,
                            'confidence' => 0.75,
                        ];
                    }
                }
            }
        }

        return array_column($crossRisks, 'cross_allergen');
    }

    private function containsIngredient(array $ingredients, string $target): bool
    {
        foreach ($ingredients as $ingredient) {
            if (str_contains(strtolower($ingredient), strtolower($target))) {
                return true;
            }
        }

        return false;
    }

    public function addCrossAllergyRule(string $allergy, string $crossAllergen, float $confidence = 0.75): void
    {
        // This would persist to database for dynamic rule updates
        // For now, we use the static map
    }
}
