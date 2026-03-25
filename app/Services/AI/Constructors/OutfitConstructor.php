<?php

declare(strict_types=1);

namespace App\Services\AI\Constructors;

use App\Models\User;
use App\Services\RecommendationService;

final class OutfitConstructor extends BaseConstructor
{
    public function __construct(private readonly RecommendationService $recommendationService)
    {
    }

    public function build(User $user, array $inputParams, ?array $imageAnalysis): array
    {
        $tasteProfile = $this->getTasteProfile($user);
        $usedTastes = [];

        $context = [
            'vertical' => 'Fashion',
            'body_type' => $imageAnalysis['body_type'] ?? null,
            'season' => $inputParams['season'] ?? 'all',
            'style_preference' => $tasteProfile['styles']['fashion'] ?? 'casual',
        ];

        if (!empty($tasteProfile['preferred_sizes'])) {
            $context['sizes'] = $tasteProfile['preferred_sizes'];
            $usedTastes[] = 'preferred_sizes';
        }
        if (!empty($tasteProfile['preferred_colors'])) {
            $context['colors'] = $tasteProfile['preferred_colors'];
            $usedTastes[] = 'preferred_colors';
        }

        $recommendations = $this->recommendationService->getForUser($user->id, 'FashionProduct', $context);

        $confidence = $this->calculateConfidence($usedTastes, $recommendations->count());

        return [
            'recommendations' => $recommendations->toArray(),
            'used_taste_profile' => $usedTastes,
            'confidence_score' => $confidence,
        ];
    }
}
