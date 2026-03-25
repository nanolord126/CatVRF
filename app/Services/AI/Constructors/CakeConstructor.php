<?php

declare(strict_types=1);

namespace App\Services\AI\Constructors;

use App\Models\User;
use App\Services\RecommendationService;

final class CakeConstructor extends BaseConstructor
{
    public function __construct(private readonly RecommendationService $recommendationService)
    {
    }

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
