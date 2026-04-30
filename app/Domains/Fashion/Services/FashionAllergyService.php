<?php

declare(strict_types=1);

namespace App\Domains\Fashion\Services;

use App\Shared\Domain\Entities\Material;
use App\Shared\Domain\Entities\MaterialAllergy;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * FashionAllergyService — Context-aware allergy checking for Fashion vertical.
 *
 * Healthcare compliance (152-ФЗ, ФЗ-323). Only shows relevant allergies
 * for fashion products (e.g., no gluten allergies for clothing).
 *
 * @version 2026.1
 */
final class FashionAllergyService
{
    private const CACHE_TTL_DAYS = 7;

    /**
     * Relevant allergen types for fashion products.
     */
    private const FASHION_RELEVANT_ALLERGENS = [
        MaterialAllergy::ALLERGEN_TYPE_CONTACT_DERMATITIS,
        MaterialAllergy::ALLERGEN_TYPE_TEXTILE,
        MaterialAllergy::ALLERGEN_TYPE_METAL,
        MaterialAllergy::ALLERGEN_TYPE_RUBBER,
        MaterialAllergy::ALLERGEN_TYPE_LATEX,
        MaterialAllergy::ALLERGEN_TYPE_CHEMICAL,
    ];

    /**
     * Check if a product is safe for user with given allergies.
     *
     * @param array $materials List of material names used in product
     * @param array $userAllergies List of allergen types user is allergic to
     * @return array ['is_safe' => bool, 'conflicts' => array, 'severity' => string|null]
     */
    public function checkProductSafety(array $materials, array $userAllergies): array
    {
        $relevantAllergies = $this->filterRelevantAllergies($userAllergies);

        if (empty($relevantAllergies)) {
            return [
                'is_safe' => true,
                'conflicts' => [],
                'severity' => null,
            ];
        }

        $conflicts = [];
        $maxSeverity = null;

        foreach ($materials as $materialName) {
            $materialAllergies = MaterialAllergy::getByMaterial($materialName);

            foreach ($materialAllergies as $allergy) {
                if (in_array($allergy['allergen_type'], $relevantAllergies, true)) {
                    $conflicts[] = [
                        'material' => $materialName,
                        'allergen_type' => $allergy['allergen_type'],
                        'severity' => $allergy['severity'],
                        'description' => $allergy['description'],
                        'medical_reference' => $allergy['medical_reference'],
                    ];

                    $maxSeverity = $this->getMaxSeverity($maxSeverity, $allergy['severity']);
                }
            }
        }

        return [
            'is_safe' => empty($conflicts),
            'conflicts' => $conflicts,
            'severity' => $maxSeverity,
        ];
    }

    /**
     * Check if a single material is safe for user with given allergies.
     */
    public function checkMaterialSafety(string $materialName, array $userAllergies): bool
    {
        $relevantAllergies = $this->filterRelevantAllergies($userAllergies);

        if (empty($relevantAllergies)) {
            return true;
        }

        $materialAllergies = MaterialAllergy::getByMaterial($materialName);

        foreach ($materialAllergies as $allergy) {
            if (in_array($allergy['allergen_type'], $relevantAllergies, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get relevant allergen types for fashion context.
     */
    public function getRelevantAllergenTypes(): array
    {
        return self::FASHION_RELEVANT_ALLERGENS;
    }

    /**
     * Filter user allergies to only fashion-relevant ones.
     */
    private function filterRelevantAllergies(array $userAllergies): array
    {
        return array_intersect($userAllergies, self::FASHION_RELEVANT_ALLERGENS);
    }

    /**
     * Get maximum severity level.
     */
    private function getMaxSeverity(?string $current, string $new): string
    {
        $severityOrder = [
            MaterialAllergy::SEVERITY_MILD => 1,
            MaterialAllergy::SEVERITY_MODERATE => 2,
            MaterialAllergy::SEVERITY_SEVERE => 3,
            MaterialAllergy::SEVERITY_LIFE_THREATENING => 4,
        ];

        $currentLevel = $current ? ($severityOrder[$current] ?? 0) : 0;
        $newLevel = $severityOrder[$new] ?? 0;

        return $newLevel > $currentLevel ? $new : ($current ?? $new);
    }

    /**
     * Get materials that are safe for user with given allergies.
     */
    public function getSafeMaterials(array $userAllergies): array
    {
        $cacheKey = 'fashion:safe_materials:' . md5(json_encode($userAllergies));

        return Cache::remember($cacheKey, now()->addDays(self::CACHE_TTL_DAYS), function () use ($userAllergies) {
            $materials = Material::active()->get();
            $safeMaterials = [];

            foreach ($materials as $material) {
                if ($this->checkMaterialSafety($material->name, $userAllergies)) {
                    $safeMaterials[] = [
                        'name' => $material->name,
                        'slug' => $material->slug,
                        'category' => $material->category,
                        'description' => $material->description,
                        'is_sustainable' => $material->is_sustainable,
                    ];
                }
            }

            return $safeMaterials;
        });
    }

    /**
     * Get materials that are unsafe for user with given allergies.
     */
    public function getUnsafeMaterials(array $userAllergies): array
    {
        $cacheKey = 'fashion:unsafe_materials:' . md5(json_encode($userAllergies));

        return Cache::remember($cacheKey, now()->addDays(self::CACHE_TTL_DAYS), function () use ($userAllergies) {
            $materials = Material::active()->get();
            $unsafeMaterials = [];

            foreach ($materials as $material) {
                if (!$this->checkMaterialSafety($material->name, $userAllergies)) {
                    $allergenTypes = MaterialAllergy::getAllergenTypesForMaterial($material->name);
                    $relevantAllergenTypes = array_intersect($allergenTypes, self::FASHION_RELEVANT_ALLERGENS);

                    $unsafeMaterials[] = [
                        'name' => $material->name,
                        'slug' => $material->slug,
                        'category' => $material->category,
                        'description' => $material->description,
                        'allergen_types' => array_values($relevantAllergenTypes),
                    ];
                }
            }

            return $unsafeMaterials;
        });
    }

    /**
     * Log allergy conflict for audit purposes (compliance 152-ФЗ).
     */
    public function logAllergyConflict(
        int $userId,
        string $productId,
        array $conflicts,
        string $severity
    ): void {
        Log::warning('Fashion allergy conflict detected', [
            'user_id' => $userId,
            'product_id' => $productId,
            'conflicts' => $conflicts,
            'severity' => $severity,
            'timestamp' => now()->toIso8601String(),
            'compliance' => '152-ФЗ, ФЗ-323',
        ]);
    }

    /**
     * Check if product requires allergy warning.
     */
    public function requiresAllergyWarning(array $materials, array $userAllergies): bool
    {
        $safetyCheck = $this->checkProductSafety($materials, $userAllergies);

        if ($safetyCheck['is_safe']) {
            return false;
        }

        // Only show warning for moderate or higher severity
        return in_array($safetyCheck['severity'], [
            MaterialAllergy::SEVERITY_MODERATE,
            MaterialAllergy::SEVERITY_SEVERE,
            MaterialAllergy::SEVERITY_LIFE_THREATENING,
        ], true);
    }

    /**
     * Get allergy warning message for product.
     */
    public function getAllergyWarningMessage(array $materials, array $userAllergies): ?string
    {
        $safetyCheck = $this->checkProductSafety($materials, $userAllergies);

        if ($safetyCheck['is_safe']) {
            return null;
        }

        $severity = $safetyCheck['severity'];
        $conflictCount = count($safetyCheck['conflicts']);

        return match ($severity) {
            MaterialAllergy::SEVERITY_MILD => "Этот продукт содержит материалы, которые могут вызвать легкую аллергическую реакцию.",
            MaterialAllergy::SEVERITY_MODERATE => "Внимание: этот продукт содержит {$conflictCount} материалов, которые могут вызвать аллергическую реакцию.",
            MaterialAllergy::SEVERITY_SEVERE => "Осторожно: этот продукт содержит материалы, которые могут вызвать тяжелую аллергическую реакцию.",
            MaterialAllergy::SEVERITY_LIFE_THREATENING => "ОПАСНОСТЬ: этот продукт содержит материалы, которые могут вызвать угрожающую жизни аллергическую реакцию.",
            default => null,
        };
    }
}
