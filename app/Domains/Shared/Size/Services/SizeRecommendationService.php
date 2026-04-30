<?php

declare(strict_types=1);

namespace App\Domains\Shared\Size\Services;

use App\Domains\Footwear\Models\FootSizeChart;
use App\Models\UserBodyProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class SizeRecommendationService
{
    private const CACHE_TTL_HOURS = 48;

    public function recommendClothingSize(
        int $userId,
        string $brand = null,
        string $category = null
    ): array {
        $cacheKey = "clothing_size_recommendation:{$userId}:{$brand}:{$category}";

        return Cache::remember($cacheKey, now()->addHours(self::CACHE_TTL_HOURS), function () use ($userId, $brand, $category) {
            $profile = UserBodyProfile::where('user_id', $userId)->first();

            if (!$profile) {
                return [
                    'success' => false,
                    'error' => 'User body profile not found',
                    'requires_profile' => true,
                ];
            }

            if (!$profile->has_complete_profile) {
                return [
                    'success' => false,
                    'error' => 'Incomplete body profile',
                    'completeness' => $profile->profile_completeness,
                    'requires_profile' => true,
                ];
            }

            $recommendation = $this->calculateClothingSize($profile, $brand, $category);

            return [
                'success' => true,
                'user_id' => $userId,
                'profile_id' => $profile->id,
                'recommendation' => $recommendation,
                'confidence' => $this->calculateClothingConfidence($profile, $recommendation),
                'based_on_measurements' => [
                    'height_cm' => $profile->height_cm,
                    'weight_kg' => $profile->weight_kg,
                    'chest_cm' => $profile->chest_circumference_cm,
                    'waist_cm' => $profile->waist_circumference_cm,
                    'hip_cm' => $profile->hip_circumference_cm,
                    'body_type' => $profile->body_type,
                ],
                'tips' => $this->getClothingTips($profile, $recommendation),
            ];
        });
    }

    public function recommendFootwearSize(
        int $userId,
        string $brand = null,
        string $gender = null
    ): array {
        $cacheKey = "footwear_size_recommendation:{$userId}:{$brand}:{$gender}";

        return Cache::remember($cacheKey, now()->addHours(self::CACHE_TTL_HOURS), function () use ($userId, $brand, $gender) {
            $profile = UserBodyProfile::where('user_id', $userId)->first();

            if (!$profile) {
                return [
                    'success' => false,
                    'error' => 'User body profile not found',
                    'requires_profile' => true,
                ];
            }

            if (!$profile->foot_length_cm || !$profile->foot_width_cm) {
                return [
                    'success' => false,
                    'error' => 'Foot measurements required',
                    'requires_foot_measurements' => true,
                ];
            }

            $gender = $gender ?? $profile->gender;
            $recommendation = FootSizeChart::getRecommendedSizeForFootMeasurements(
                $profile->foot_length_cm,
                $profile->foot_width_cm,
                $gender,
                $brand
            );

            if (!$recommendation) {
                return [
                    'success' => false,
                    'error' => 'No size chart found for the given parameters',
                ];
            }

            return [
                'success' => true,
                'user_id' => $userId,
                'profile_id' => $profile->id,
                'recommendation' => $recommendation,
                'based_on_measurements' => [
                    'foot_length_cm' => $profile->foot_length_cm,
                    'foot_width_cm' => $profile->foot_width_cm,
                    'gender' => $gender,
                ],
                'tips' => $this->getFootwearTips($profile, $recommendation),
            ];
        });
    }

    public function recommendSizeForProduct(
        int $userId,
        int $productId,
        string $productType
    ): array {
        return match($productType) {
            'fashion', 'clothing' => $this->recommendClothingSizeForProduct($userId, $productId),
            'footwear', 'shoes' => $this->recommendFootwearSizeForProduct($userId, $productId),
            default => [
                'success' => false,
                'error' => 'Unsupported product type',
            ],
        };
    }

    public function getSizeFromHistoricalPurchases(int $userId, string $category): ?array
    {
        $cacheKey = "historical_size:{$userId}:{$category}";

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($userId, $category) {
            $historicalSizes = $this->getUserHistoricalSizes($userId, $category);

            if ($historicalSizes->isEmpty()) {
                return null;
            }

            $sizeFrequency = $historicalSizes->countBy('size')->sortDesc();
            $mostCommonSize = $sizeFrequency->keys()->first();

            $averageRating = $historicalSizes->avg('size_accuracy_rating');

            return [
                'recommended_size' => $mostCommonSize,
                'confidence' => $this->calculateHistoricalConfidence($sizeFrequency, $historicalSizes->count()),
                'based_on_purchases' => $historicalSizes->count(),
                'average_accuracy_rating' => $averageRating,
                'size_distribution' => $sizeFrequency->toArray(),
            ];
        });
    }

    public function getSizeFromReturns(int $userId, string $category): ?array
    {
        $cacheKey = "return_size:{$userId}:{$category}";

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($userId, $category) {
            $returns = $this->getUserReturns($userId, $category);

            if ($returns->isEmpty()) {
                return null;
            }

            $returnReasons = $returns->groupBy('return_reason')->map->count();
            $sizeIssues = $returns->where('return_reason', 'size_issue');

            if ($sizeIssues->isEmpty()) {
                return null;
            }

            $sizeChangeFrequency = $sizeIssues->countBy('size_change_direction')->sortDesc();

            return [
                'has_size_issues' => true,
                'total_returns' => $returns->count(),
                'size_related_returns' => $sizeIssues->count(),
                'common_issue' => $sizeChangeFrequency->keys()->first(),
                'recommendation' => $this->getReturnBasedRecommendation($sizeChangeFrequency),
            ];
        });
    }

    private function calculateClothingSize(UserBodyProfile $profile, ?string $brand, ?string $category): array
    {
        $baseSize = $this->getBaseClothingSize($profile);
        $brandAdjustment = $brand ? $this->getBrandSizeAdjustment($brand, $profile->gender) : 0;
        $categoryAdjustment = $category ? $this->getCategorySizeAdjustment($category, $profile->body_type) : 0;

        $adjustedSize = $this->applySizeAdjustment($baseSize, $brandAdjustment + $categoryAdjustment);

        return [
            'size' => $adjustedSize,
            'size_system' => $profile->preferred_size_system ?? 'eu',
            'fit_type' => $profile->preferred_fit ?? 'regular',
            'brand_adjustment' => $brandAdjustment,
            'category_adjustment' => $categoryAdjustment,
            'body_type' => $profile->body_type,
            'calculated_shape' => $profile->body_shape_calculated,
        ];
    }

    private function getBaseClothingSize(UserBodyProfile $profile): string
    {
        if ($profile->gender === UserBodyProfile::GENDER_FEMALE) {
            return $this->getFemaleClothingSize($profile);
        } else {
            return $this->getMaleClothingSize($profile);
        }
    }

    private function getFemaleClothingSize(UserBodyProfile $profile): string
    {
        $chest = $profile->chest_circumference_cm ?? $profile->bust_circumference_cm;
        $waist = $profile->waist_circumference_cm;
        $hip = $profile->hip_circumference_cm;

        if (!$chest || !$waist || !$hip) {
            return 'M';
        }

        $maxMeasurement = max($chest, $waist, $hip);

        return match(true) {
            $maxMeasurement < 84 => 'XS',
            $maxMeasurement < 89 => 'S',
            $maxMeasurement < 94 => 'M',
            $maxMeasurement < 99 => 'L',
            $maxMeasurement < 104 => 'XL',
            $maxMeasurement < 112 => '2XL',
            default => '3XL',
        };
    }

    private function getMaleClothingSize(UserBodyProfile $profile): string
    {
        $chest = $profile->chest_circumference_cm;
        $waist = $profile->waist_circumference_cm;

        if (!$chest || !$waist) {
            return 'M';
        }

        $maxMeasurement = max($chest, $waist);

        return match(true) {
            $maxMeasurement < 92 => 'XS',
            $maxMeasurement < 100 => 'S',
            $maxMeasurement < 108 => 'M',
            $maxMeasurement < 116 => 'L',
            $maxMeasurement < 124 => 'XL',
            $maxMeasurement < 134 => '2XL',
            default => '3XL',
        };
    }

    private function getBrandSizeAdjustment(string $brand, string $gender): int
    {
        $brandSizing = [
            'zara' => -1,
            'h&m' => 0,
            'uniqlo' => 1,
            'gucci' => -1,
            'prada' => -1,
            'nike' => 0,
            'adidas' => 0,
            'levis' => 0,
            'tommy_hilfiger' => 1,
            'calvin_klein' => 0,
        ];

        return $brandSizing[strtolower($brand)] ?? 0;
    }

    private function getCategorySizeAdjustment(string $category, string $bodyType): int
    {
        $categorySizing = [
            'jackets' => 1,
            'coats' => 1,
            'sweaters' => 0,
            'shirts' => 0,
            'pants' => 0,
            'jeans' => -1,
            'dresses' => 0,
            'skirts' => 0,
        ];

        $adjustment = $categorySizing[strtolower($category)] ?? 0;

        if ($bodyType === UserBodyProfile::BODY_TYPE_PLUS_SIZE) {
            $adjustment += 1;
        } elseif ($bodyType === UserBodyProfile::BODY_TYPE_PETITE) {
            $adjustment -= 1;
        }

        return $adjustment;
    }

    private function applySizeAdjustment(string $baseSize, int $adjustment): string
    {
        $sizeOrder = ['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', '5XL'];
        $currentIndex = array_search($baseSize, $sizeOrder, true);

        if ($currentIndex === false) {
            return $baseSize;
        }

        $newIndex = max(0, min(count($sizeOrder) - 1, $currentIndex + $adjustment));

        return $sizeOrder[$newIndex];
    }

    private function calculateClothingConfidence(UserBodyProfile $profile, array $recommendation): float
    {
        $confidence = 0.85;

        if ($profile->is_verified) {
            $confidence += 0.10;
        }

        if ($profile->accuracy_score > 0.8) {
            $confidence += 0.05;
        }

        if ($profile->profile_completeness >= 90) {
            $confidence += 0.05;
        }

        return min(0.99, $confidence);
    }

    private function getClothingTips(UserBodyProfile $profile, array $recommendation): array
    {
        $tips = [];

        if ($profile->body_type === UserBodyProfile::BODY_TYPE_HOURGLASS) {
            $tips[] = 'Consider fitted styles that accentuate your waist';
        } elseif ($profile->body_type === UserBodyProfile::BODY_TYPE_PEAR) {
            $tips[] = 'A-line skirts and dresses work well for your body type';
        } elseif ($profile->body_type === UserBodyProfile::BODY_TYPE_APPLE) {
            $tips[] = 'Empire waist styles help balance your proportions';
        }

        if ($recommendation['fit_type'] === UserBodyProfile::PREFERRED_FIT_SLIM) {
            $tips[] = 'Slim fit may feel tighter, consider sizing up for comfort';
        }

        if ($profile->shoulder_width_cm && $profile->hip_circumference_cm) {
            $ratio = $profile->shoulder_width_cm / $profile->hip_circumference_cm;
            if ($ratio > 0.6) {
                $tips[] = 'Consider styles with broader shoulders or boat necklines';
            }
        }

        return $tips;
    }

    private function getFootwearTips(UserBodyProfile $profile, array $recommendation): array
    {
        $tips = [];

        if ($recommendation['foot_width_fit'] === 'consider_width_adjustment') {
            $tips[] = 'Consider wide or extra-wide options for better comfort';
        }

        if ($profile->foot_width_cm > 10) {
            $tips[] = 'Your feet are wider than average, look for wide-fit options';
        }

        if ($recommendation['foot_length_fit'] !== 'perfect_fit') {
            $tips[] = 'Try both recommended sizes to find the best fit';
        }

        if ($profile->arch_support_level ?? null) {
            $tips[] = 'Consider shoes with appropriate arch support';
        }

        return $tips;
    }

    private function recommendClothingSizeForProduct(int $userId, int $productId): array
    {
        $product = \App\Domains\Fashion\Models\FashionProduct::find($productId);

        if (!$product) {
            return [
                'success' => false,
                'error' => 'Product not found',
            ];
        }

        $baseRecommendation = $this->recommendClothingSize($userId, $product->brand, null);

        if (!$baseRecommendation['success']) {
            return $baseRecommendation;
        }

        $historical = $this->getSizeFromHistoricalPurchases($userId, 'clothing');
        $returns = $this->getSizeFromReturns($userId, 'clothing');

        $finalRecommendation = $this->mergeRecommendations(
            $baseRecommendation['recommendation']['size'],
            $historical['recommended_size'] ?? null,
            $returns['recommendation'] ?? null
        );

        return [
            'success' => true,
            'product_id' => $productId,
            'product_name' => $product->name,
            'brand' => $product->brand,
            'recommended_size' => $finalRecommendation,
            'base_recommendation' => $baseRecommendation['recommendation'],
            'historical_data' => $historical,
            'return_data' => $returns,
            'confidence' => $this->calculateFinalConfidence($baseRecommendation, $historical, $returns),
        ];
    }

    private function recommendFootwearSizeForProduct(int $userId, int $productId): array
    {
        $product = \App\Domains\Footwear\Models\FootwearProduct::find($productId);

        if (!$product) {
            return [
                'success' => false,
                'error' => 'Product not found',
            ];
        }

        $baseRecommendation = $this->recommendFootwearSize($userId, $product->brand, null);

        if (!$baseRecommendation['success']) {
            return $baseRecommendation;
        }

        $historical = $this->getSizeFromHistoricalPurchases($userId, 'footwear');
        $returns = $this->getSizeFromReturns($userId, 'footwear');

        $finalRecommendation = $this->mergeRecommendations(
            $baseRecommendation['recommendation']['size_eu'],
            $historical['recommended_size'] ?? null,
            $returns['recommendation'] ?? null
        );

        return [
            'success' => true,
            'product_id' => $productId,
            'product_name' => $product->name,
            'brand' => $product->brand,
            'recommended_size' => $finalRecommendation,
            'base_recommendation' => $baseRecommendation['recommendation'],
            'historical_data' => $historical,
            'return_data' => $returns,
            'confidence' => $this->calculateFinalConfidence($baseRecommendation, $historical, $returns),
        ];
    }

    private function mergeRecommendations(string $baseSize, ?string $historicalSize, ?string $returnRecommendation): string
    {
        if ($historicalSize && $historicalSize === $baseSize) {
            return $baseSize;
        }

        if ($returnRecommendation) {
            return match($returnRecommendation) {
                'size_up' => $this->sizeUp($baseSize),
                'size_down' => $this->sizeDown($baseSize),
                default => $baseSize,
            };
        }

        if ($historicalSize) {
            return $historicalSize;
        }

        return $baseSize;
    }

    private function sizeUp(string $size): string
    {
        $sizeOrder = ['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', '5XL'];
        $currentIndex = array_search($size, $sizeOrder, true);

        if ($currentIndex !== false && $currentIndex < count($sizeOrder) - 1) {
            return $sizeOrder[$currentIndex + 1];
        }

        return $size;
    }

    private function sizeDown(string $size): string
    {
        $sizeOrder = ['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', '5XL'];
        $currentIndex = array_search($size, $sizeOrder, true);

        if ($currentIndex !== false && $currentIndex > 0) {
            return $sizeOrder[$currentIndex - 1];
        }

        return $size;
    }

    private function calculateFinalConfidence(array $base, ?array $historical, ?array $returns): float
    {
        $confidence = $base['confidence'] ?? 0.85;

        if ($historical && $historical['confidence'] > 0.8) {
            $confidence = ($confidence + $historical['confidence']) / 2;
        }

        if ($returns && $returns['has_size_issues']) {
            $confidence -= 0.15;
        }

        return max(0.5, min(0.99, $confidence));
    }

    private function getUserHistoricalSizes(int $userId, string $category): Collection
    {
        return collect();
    }

    private function getUserReturns(int $userId, string $category): Collection
    {
        return collect();
    }

    private function calculateHistoricalConfidence(Collection $sizeFrequency, int $totalPurchases): float
    {
        if ($totalPurchases < 3) {
            return 0.5;
        }

        $mostCommonCount = $sizeFrequency->first();
        $frequency = $mostCommonCount / $totalPurchases;

        return round($frequency, 2);
    }

    private function getReturnBasedRecommendation(Collection $sizeChangeFrequency): string
    {
        $mostCommon = $sizeChangeFrequency->keys()->first();

        return match($mostCommon) {
            'size_up' => 'size_up',
            'size_down' => 'size_down',
            default => 'no_change',
        };
    }

    public function clearUserCache(int $userId): void
    {
        Cache::tags(["size_recommendation:{$userId}"])->flush();
    }
}
