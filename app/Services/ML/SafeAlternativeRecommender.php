<?php

declare(strict_types=1);

namespace App\Services\ML;

use Modules\Contraindications\Application\Services\ContraindicationService;
use Modules\Contraindications\Domain\ValueObjects\Scope;

final class SafeAlternativeRecommender
{
    public function __construct(
        private readonly ContraindicationService $contraindicationService,
    ) {
    }

    /**
     * Recommend safe alternatives for a given entity
     *
     * @return array<int, array<string, mixed>>
     */
    public function recommend(
        object $entity,
        object $subject,
        Scope $scope,
        int $limit = 5
    ): array {
        $entityType = get_class($entity);
        $alternatives = [];

        // Get entities of the same type
        $similarEntities = $this->getSimilarEntities($entity, $entityType, $limit * 2);

        foreach ($similarEntities as $candidate) {
            if ($candidate->id === $entity->id) {
                continue;
            }

            // Check compatibility
            $compatibility = $this->contraindicationService->checkCompatibilityForUser(
                $entityType,
                $candidate->id,
                $subject->id,
                $scope
            );

            if ($compatibility->isCompatible) {
                $alternatives[] = [
                    'id' => $candidate->id,
                    'name' => $candidate->name ?? $candidate->title ?? 'Unknown',
                    'type' => $entityType,
                    'similarity_score' => $this->calculateSimilarity($entity, $candidate),
                ];

                if (count($alternatives) >= $limit) {
                    break;
                }
            }
        }

        return $alternatives;
    }

    /**
     * Get similar entities based on category, tags, or other attributes
     */
    private function getSimilarEntities(object $entity, string $entityType, int $limit): array
    {
        // This would be implemented based on the actual entity structure
        // For now, return entities from the same category
        
        if (method_exists($entity, 'category_id') && $entity->category_id) {
            return $entityType::where('category_id', $entity->category_id)
                ->where('is_active', true)
                ->limit($limit)
                ->get()
                ->toArray();
        }

        return $entityType::where('is_active', true)
            ->limit($limit)
            ->get()
            ->toArray();
    }

    private function calculateSimilarity(object $entity1, object $entity2): float
    {
        $score = 0.0;

        // Same category
        if (method_exists($entity1, 'category_id') && 
            method_exists($entity2, 'category_id') &&
            $entity1->category_id === $entity2->category_id) {
            $score += 0.5;
        }

        // Similar price range
        if (method_exists($entity1, 'price') && 
            method_exists($entity2, 'price')) {
            $priceDiff = abs($entity1->price - $entity2->price);
            $avgPrice = ($entity1->price + $entity2->price) / 2;
            if ($avgPrice > 0) {
                $priceRatio = $priceDiff / $avgPrice;
                $score += max(0, 0.3 - $priceRatio);
            }
        }

        return min($score, 1.0);
    }
}
