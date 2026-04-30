<?php

declare(strict_types=1);

namespace App\Domains\Shared\Contraindications\Services;

use App\Domains\Shared\Contraindications\Entities\MaterialAllergy;
use App\Domains\Shared\Contraindications\Repositories\MaterialAllergyRepositoryInterface;
use App\Domains\Shared\Contraindications\ValueObjects\AllergenType;
use App\Domains\Shared\Contraindications\ValueObjects\SeverityLevel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

final class MaterialAllergyService
{
    public function __construct(
        private readonly MaterialAllergyRepositoryInterface $repository,
    ) {}

    /**
     * Check if a material triggers any allergies for a user
     */
    public function checkMaterialAllergies(int $userId, string $material, string $category = 'fashion'): array
    {
        $userAllergies = $this->repository->findByUser($userId);
        $relevantAllergies = [];

        foreach ($userAllergies as $allergy) {
            if ($allergy->isRelevantForMaterial($material) && $allergy->isRelevantForCategory($category)) {
                $relevantAllergies[] = [
                    'allergy_uuid' => $allergy->getUuid(),
                    'material_name' => $allergy->getMaterialName(),
                    'allergen_type' => $allergy->getAllergenType()->value,
                    'severity' => $allergy->getSeverity()->value,
                    'severity_label' => $allergy->getSeverity()->getLabel(),
                    'severity_color' => $allergy->getSeverity()->getColor(),
                    'description' => $allergy->getDescription(),
                    'requires_warning' => $allergy->getSeverity()->requiresWarning(),
                    'requires_block' => $allergy->getSeverity()->requiresBlock(),
                    'action_message' => $allergy->getSeverity()->getActionMessage(),
                ];
            }
        }

        // Sort by severity priority
        usort($relevantAllergies, fn($a, $b) => $b['severity'] <=> $a['severity']);

        return $relevantAllergies;
    }

    /**
     * Check multiple materials for allergies
     */
    public function checkMultipleMaterials(int $userId, array $materials, string $category = 'fashion'): array
    {
        $results = [];

        foreach ($materials as $material) {
            $results[$material] = $this->checkMaterialAllergies($userId, $material, $category);
        }

        return $results;
    }

    /**
     * Check if a product can be added to cart based on allergies
     */
    public function canAddToCart(int $userId, array $materials, string $category = 'fashion'): array
    {
        $allergies = $this->checkMultipleMaterials($userId, $materials, $category);

        $hasBlocking = false;
        $hasWarning = false;
        $blockingAllergies = [];
        $warningAllergies = [];

        foreach ($materials as $material) {
            foreach ($allergies[$material] as $allergy) {
                if ($allergy['requires_block']) {
                    $hasBlocking = true;
                    $blockingAllergies[] = $allergy;
                } elseif ($allergy['requires_warning']) {
                    $hasWarning = true;
                    $warningAllergies[] = $allergy;
                }
            }
        }

        return [
            'can_add' => !$hasBlocking,
            'has_warning' => $hasWarning,
            'blocking_allergies' => $blockingAllergies,
            'warning_allergies' => $warningAllergies,
            'all_allergies' => $allergies,
        ];
    }

    /**
     * Get safe materials for a user in a specific category
     */
    public function getSafeMaterials(int $userId, array $availableMaterials, string $category = 'fashion'): array
    {
        $safeMaterials = [];
        $userAllergies = $this->repository->findByUser($userId);

        foreach ($availableMaterials as $material) {
            $hasAllergy = false;

            foreach ($userAllergies as $allergy) {
                if ($allergy->isRelevantForMaterial($material) && $allergy->isRelevantForCategory($category)) {
                    if ($allergy->getSeverity()->requiresBlock()) {
                        $hasAllergy = true;
                        break;
                    }
                }
            }

            if (!$hasAllergy) {
                $safeMaterials[] = $material;
            }
        }

        return $safeMaterials;
    }

    /**
     * Add material allergy for a user
     */
    public function addUserAllergy(
        int $userId,
        string $materialName,
        AllergenType $allergenType,
        SeverityLevel $severity,
        array $triggeringMaterials,
        string $description,
        ?string $medicalReference = null,
    ): MaterialAllergy {
        $allergy = MaterialAllergy::create(
            materialName: $materialName,
            allergenType: $allergenType,
            severity: $severity,
            triggeringMaterials: $triggeringMaterials,
            description: $description,
            medicalReference: $medicalReference,
        );

        $this->repository->saveForUser($userId, $allergy);

        Log::info('Material allergy added for user', [
            'user_id' => $userId,
            'material_name' => $materialName,
            'severity' => $severity->value,
        ]);

        return $allergy;
    }

    /**
     * Remove material allergy for a user
     */
    public function removeUserAllergy(int $userId, string $allergyUuid): bool
    {
        $removed = $this->repository->deleteForUser($userId, $allergyUuid);

        if ($removed) {
            Log::info('Material allergy removed for user', [
                'user_id' => $userId,
                'allergy_uuid' => $allergyUuid,
            ]);
        }

        return $removed;
    }

    /**
     * Get user's allergies by category
     */
    public function getUserAllergiesByCategory(int $userId, string $category): Collection
    {
        $userAllergies = $this->repository->findByUser($userId);

        return $userAllergies->filter(fn($allergy) => $allergy->isRelevantForCategory($category));
    }

    /**
     * Get statistics for material allergies
     */
    public function getStatistics(): array
    {
        return $this->repository->getStatistics();
    }

    /**
     * Get common allergens by category
     */
    public function getCommonAllergensByCategory(string $category): array
    {
        $categoryMappings = [
            'footwear' => [
                'leather' => ['chromium', 'tanning agents'],
                'rubber' => ['latex', 'neoprene'],
                'glue' => ['formaldehyde', 'solvents'],
            ],
            'fashion' => [
                'wool' => ['lanolin'],
                'cotton' => ['dyes', 'formaldehyde'],
                'polyester' => ['dyes'],
                'metal' => ['nickel', 'cobalt'],
            ],
            'accessories' => [
                'metal' => ['nickel', 'chromium'],
                'leather' => ['chromium'],
            ],
        ];

        return $categoryMappings[$category] ?? [];
    }

    /**
     * Validate material for allergen safety
     */
    public function validateMaterial(string $material, string $category = 'fashion'): array
    {
        $commonAllergens = $this->getCommonAllergensByCategory($category);
        $detectedAllergens = [];

        foreach ($commonAllergens as $materialName => $allergens) {
            if (str_contains(strtolower($material), strtolower($materialName))) {
                $detectedAllergens[$materialName] = $allergens;
            }
        }

        return [
            'material' => $material,
            'category' => $category,
            'has_allergens' => !empty($detectedAllergens),
            'detected_allergens' => $detectedAllergens,
            'risk_level' => empty($detectedAllergens) ? 'low' : 'moderate',
        ];
    }

    /**
     * Get allergy recommendations for product display
     */
    public function getProductAllergyRecommendations(int $userId, array $productMaterials, string $category): array
    {
        $checkResult = $this->canAddToCart($userId, $productMaterials, $category);

        return [
            'is_safe' => $checkResult['can_add'],
            'has_warnings' => $checkResult['has_warning'],
            'blocking_count' => count($checkResult['blocking_allergies']),
            'warning_count' => count($checkResult['warning_allergies']),
            'recommendation' => $this->generateRecommendation($checkResult),
            'safe_alternatives' => $checkResult['can_add'] ? [] : $this->suggestAlternatives($productMaterials, $category),
        ];
    }

    private function generateRecommendation(array $checkResult): string
    {
        if ($checkResult['can_add'] && !$checkResult['has_warning']) {
            return 'Этот продукт безопасен для вас';
        }

        if ($checkResult['can_add'] && $checkResult['has_warning']) {
            return 'Продукт может вызвать лёгкую реакцию. Рекомендуем осторожность.';
        }

        if (!$checkResult['can_add']) {
            return 'Этот продукт содержит материалы, вызывающие у вас аллергию. Добавление в корзину заблокировано.';
        }

        return 'Не удалось определить безопасность продукта';
    }

    private function suggestAlternatives(array $materials, string $category): array
    {
        // This would integrate with product search to find alternatives
        // For now, return material suggestions
        $alternatives = [
            'leather' => ['synthetic leather', 'vegan leather', 'canvas'],
            'wool' => ['cotton', 'synthetic blends', 'bamboo'],
            'latex' => ['synthetic rubber', 'neoprene-free'],
            'nickel' => ['titanium', 'surgical steel', 'plastic'],
        ];

        $suggestions = [];
        foreach ($materials as $material) {
            if (isset($alternatives[strtolower($material)])) {
                $suggestions[$material] = $alternatives[strtolower($material)];
            }
        }

        return $suggestions;
    }
}
