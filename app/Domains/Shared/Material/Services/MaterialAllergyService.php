<?php

declare(strict_types=1);

namespace App\Domains\Shared\Material\Services;

use App\Domains\Shared\Material\Models\Allergen;
use App\Domains\Shared\Material\Models\Material;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class MaterialAllergyService
{
    private const CACHE_TTL_HOURS = 24;

    public function checkMaterialCompatibility(
        int $materialId,
        array $userAllergies,
        string $vertical = 'fashion'
    ): array {
        $cacheKey = "material_compatibility:{$materialId}:" . md5(serialize($userAllergies));

        return Cache::remember($cacheKey, now()->addHours(self::CACHE_TTL_HOURS), function () use ($materialId, $userAllergies, $vertical) {
            $material = Material::with('allergens')->find($materialId);

            if (!$material) {
                return [
                    'is_compatible' => false,
                    'error' => 'Material not found',
                ];
            }

            $relevantAllergens = $material->allergens
                ->filter(fn (Allergen $allergen) => $allergen->isRelevantForVertical($vertical))
                ->filter(fn (Allergen $allergen) => in_array($allergen->name, $userAllergies, true));

            $conflicts = $relevantAllergens->map(function (Allergen $allergen) {
                return [
                    'allergen' => $allergen->name,
                    'scientific_name' => $allergen->scientific_name,
                    'severity' => $allergen->severity,
                    'severity_level' => $allergen->severity_level,
                    'symptoms' => $allergen->symptoms,
                    'prevention' => $allergen->prevention_tips,
                    'prevalence' => $allergen->prevalence_percentage,
                ];
            })->values()->toArray();

            $hasConflicts = !empty($conflicts);
            $hasWarning = !$material->is_hypoallergenic && in_array('sensitive_skin', $userAllergies, true);

            return [
                'is_compatible' => !$hasConflicts,
                'material' => [
                    'id' => $material->id,
                    'name' => $material->name,
                    'category' => $material->category,
                    'is_hypoallergenic' => $material->is_hypoallergenic,
                    'is_natural' => $material->is_natural,
                ],
                'conflicts' => $conflicts,
                'warnings' => $hasWarning ? [
                    [
                        'type' => 'sensitive_skin',
                        'message' => 'Material is not hypoallergenic - may cause irritation for sensitive skin',
                    ],
                ] : [],
                'risk_level' => $this->calculateRiskLevel($conflicts),
                'recommendation' => $this->getRecommendation($hasConflicts, $hasWarning),
            ];
        });
    }

    public function checkProductCompatibility(
        object $product,
        array $userAllergies,
        string $vertical = 'fashion'
    ): array {
        $cacheKey = "product_compatibility:{$product->id}:" . md5(serialize($userAllergies));

        return Cache::remember($cacheKey, now()->addHours(self::CACHE_TTL_HOURS), function () use ($product, $userAllergies, $vertical) {
            $materials = match($vertical) {
                'fashion' => $product->materials ?? collect(),
                'footwear' => $product->materials ?? collect(),
                default => collect(),
            };

            if ($materials->isEmpty()) {
                return [
                    'is_compatible' => true,
                    'message' => 'No material information available',
                ];
            }

            $materialResults = [];
            $hasAnyConflict = false;
            $maxRiskLevel = 0;

            foreach ($materials as $materialRelation) {
                $material = $materialRelation instanceof Material ? $materialRelation : $materialRelation->material;
                $percentage = $materialRelation->pivot->percentage ?? 100;
                $isPrimary = $materialRelation->pivot->is_primary ?? true;

                $result = $this->checkMaterialCompatibility($material->id, $userAllergies, $vertical);
                $result['percentage'] = $percentage;
                $result['is_primary'] = $isPrimary;

                $materialResults[] = $result;

                if (!$result['is_compatible']) {
                    $hasAnyConflict = true;
                    $maxRiskLevel = max($maxRiskLevel, $result['risk_level']);
                }
            }

            $overallRisk = $this->calculateOverallRisk($materialResults);

            return [
                'is_compatible' => !$hasAnyConflict,
                'product_id' => $product->id,
                'product_name' => $product->name ?? 'Unknown',
                'materials' => $materialResults,
                'overall_risk_level' => $overallRisk,
                'recommendation' => $this->getProductRecommendation($hasAnyConflict, $overallRisk),
            ];
        });
    }

    public function getSafeAlternatives(
        int $materialId,
        array $userAllergies,
        string $vertical = 'fashion',
        ?string $category = null
    ): Collection {
        $originalMaterial = Material::find($materialId);

        if (!$originalMaterial) {
            return collect();
        }

        $query = Material::where('id', '!=', $materialId)
            ->where('is_hypoallergenic', true);

        if ($category) {
            $query->where('category', $category);
        } else {
            $query->where('category', $originalMaterial->category);
        }

        $alternatives = $query->get();

        return $alternatives->filter(function (Material $material) use ($userAllergies, $vertical) {
            $compatibility = $this->checkMaterialCompatibility($material->id, $userAllergies, $vertical);
            return $compatibility['is_compatible'];
        })->map(function (Material $material) use ($originalMaterial) {
            return [
                'id' => $material->id,
                'name' => $material->name,
                'category' => $material->category,
                'is_natural' => $material->is_natural,
                'is_sustainable' => $material->is_sustainable,
                'environmental_score' => $material->environmental_score,
                'similarity_score' => $this->calculateSimilarity($originalMaterial, $material),
            ];
        })->sortByDesc('similarity_score')->values();
    }

    public function getAllergenProfile(array $userAllergies): array
    {
        $allergens = Allergen::whereIn('name', $userAllergies)->get();

        return [
            'total_allergens' => $allergens->count(),
            'high_risk_count' => $allergens->where('severity', Allergen::SEVERITY_LIFE_THREATENING)->count(),
            'moderate_risk_count' => $allergens->where('severity', Allergen::SEVERITY_SEVERE)->count(),
            'low_risk_count' => $allergens->whereIn('severity', [Allergen::SEVERITY_MILD, Allergen::SEVERITY_MODERATE])->count(),
            'allergens' => $allergens->map(fn (Allergen $a) => $a->risk_assessment)->toArray(),
            'common_materials_to_avoid' => $this->getMaterialsContainingAllergens($userAllergies),
        ];
    }

    public function getMaterialsContainingAllergens(array $userAllergies): array
    {
        $cacheKey = 'materials_with_allergens:' . md5(serialize($userAllergies));

        return Cache::remember($cacheKey, now()->addHours(self::CACHE_TTL_HOURS), function () use ($userAllergies) {
            return Material::whereHas('allergens', function ($query) use ($userAllergies) {
                $query->whereIn('name', $userAllergies);
            })->with('allergens')->get()->map(function (Material $material) {
                return [
                    'id' => $material->id,
                    'name' => $material->name,
                    'category' => $material->category,
                    'contained_allergens' => $material->allergens
                        ->whereIn('name', $userAllergies)
                        ->pluck('name')
                        ->toArray(),
                ];
            })->toArray();
        });
    }

    private function calculateRiskLevel(array $conflicts): int
    {
        if (empty($conflicts)) {
            return 0;
        }

        $maxSeverity = max(array_column($conflicts, 'severity_level'));
        $conflictCount = count($conflicts);

        if ($maxSeverity >= 4) {
            return 4; // Life threatening
        } elseif ($maxSeverity >= 3) {
            return $conflictCount > 1 ? 4 : 3; // Severe
        } elseif ($maxSeverity >= 2) {
            return $conflictCount > 2 ? 3 : 2; // Moderate
        } else {
            return 1; // Mild
        }
    }

    private function calculateOverallRisk(array $materialResults): int
    {
        $riskLevels = array_column($materialResults, 'risk_level');
        $maxRisk = max($riskLevels);

        if ($maxRisk >= 4) {
            return 4;
        }

        $highRiskCount = count(array_filter($riskLevels, fn ($r) => $r >= 3));
        $moderateRiskCount = count(array_filter($riskLevels, fn ($r) => $r === 2));

        if ($highRiskCount >= 2) {
            return 4;
        } elseif ($highRiskCount === 1) {
            return 3;
        } elseif ($moderateRiskCount >= 3) {
            return 3;
        } elseif ($moderateRiskCount >= 1) {
            return 2;
        }

        return 1;
    }

    private function calculateSimilarity(Material $original, Material $alternative): float
    {
        $score = 0.0;
        $maxScore = 100.0;

        if ($original->category === $alternative->category) $score += 30;
        if ($original->type === $alternative->type) $score += 20;
        if ($original->is_natural === $alternative->is_natural) $score += 15;
        if ($original->is_breathable === $alternative->is_breathable) $score += 10;
        if ($original->is_water_resistant === $alternative->is_water_resistant) $score += 10;
        if ($original->is_stretchable === $alternative->is_stretchable) $score += 10;

        $weightDiff = abs($original->weight_gsm - $alternative->weight_gsm);
        if ($weightDiff < 50) $score += 5;

        return min($score, $maxScore);
    }

    private function getRecommendation(bool $hasConflict, bool $hasWarning): string
    {
        if ($hasConflict) {
            return 'NOT_RECOMMENDED - Contains allergens that may cause adverse reactions';
        } elseif ($hasWarning) {
            return 'CAUTION - May cause irritation for sensitive skin';
        } else {
            return 'SAFE - No known allergens';
        }
    }

    private function getProductRecommendation(bool $hasConflict, int $riskLevel): string
    {
        return match(true) {
            $hasConflict && $riskLevel >= 4 => 'DANGEROUS - Contains life-threatening allergens',
            $hasConflict && $riskLevel >= 3 => 'NOT_RECOMMENDED - Contains severe allergens',
            $hasConflict && $riskLevel >= 2 => 'USE_CAUTION - Contains moderate allergens',
            $hasConflict => 'CAUTION - Contains mild allergens',
            default => 'SAFE - No allergen conflicts detected',
        };
    }

    public function clearMaterialCache(int $materialId): void
    {
        Cache::forget("material_compatibility:{$materialId}");
        Cache::tags(['material_compatibility'])->flush();
    }

    public function clearProductCache(int $productId): void
    {
        Cache::forget("product_compatibility:{$productId}");
        Cache::tags(['product_compatibility'])->flush();
    }
}
