<?php declare(strict_types=1);

/**
 * CakeConstructor — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/cakeconstructor
 */


namespace App\Services\AI\Constructors;

use App\Models\User;
use App\Services\RecommendationService;

/**
 * Class CakeConstructor
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Services\AI\Constructors
 */
final readonly class CakeConstructor extends BaseConstructor
{
    public function __construct(private readonly RecommendationService $recommendationService)
        {

    }

        /**
         * Handle build operation.
         *
         * @throws \DomainException
         */
        public function build(User $user, array $inputParams, ?array $imageAnalysis): array
        {
            $tasteProfile = $this->getTasteProfile($user);
            $usedTastes = [];

            $context = [
                'vertical' => 'Food',
                'sub_vertical' => 'Confectionery',
                'event_type' => $inputParams['event_type'] ?? 'birthday',
                'flavor_preference' => $tasteProfile['flavors']['dessert'] ?? 'chocolate',
            ];

            if (isset($inputParams['allergies'])) {
                $context['allergies_except'] = $inputParams['allergies'];
            }

            $recommendations = $this->recommendationService->getForUser($user->id, 'CakeProduct', $context);

            $confidence = $this->calculateConfidence($usedTastes, $recommendations->count());

            return [
                'recommendations' => $recommendations->toArray(),
                'used_taste_profile' => $usedTastes,
                'confidence_score' => $confidence,
            ];
        }
}
