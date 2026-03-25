<?php

declare(strict_types=1);

namespace App\Services\AI\Constructors;

use App\Models\User;
use App\Services\RecommendationService;

final class InteriorConstructor extends BaseConstructor
{
    public function __construct(private readonly RecommendationService $recommendationService)
    {
    }

    public function build(User $user, array $inputParams, ?array $imageAnalysis): array
    {
        $tasteProfile = $this->getTasteProfile($user);
        $usedTastes = [];

        $context = [
            'vertical' => 'RealEstate',
            'sub_vertical' => 'InteriorDesign',
            'room_type' => $imageAnalysis['room_type'] ?? $inputParams['room_type'] ?? null,
            'style_preference' => $tasteProfile['styles']['interior'] ?? 'modern',
        ];

        if (isset($tasteProfile['colors']['primary'])) {
            $context['color_palette'] = $tasteProfile['colors']['primary'];
            $usedTastes[] = 'primary_color';
        }

        $recommendations = $this->recommendationService->getForUser($user->id, 'Furniture', $context);

        $confidence = $this->calculateConfidence($usedTastes, $recommendations->count());

        return [
            'recommendations' => $recommendations->toArray(),
            'used_taste_profile' => $usedTastes,
            'confidence_score' => $confidence,
        ];
    }
}
