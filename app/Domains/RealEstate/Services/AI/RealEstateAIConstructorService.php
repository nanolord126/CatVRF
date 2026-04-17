<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Services\AI;

use App\Domains\RealEstate\Models\Property;
use Illuminate\Contracts\Cache\Repository as Cache;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Str;

final readonly class RealEstateAIConstructorService
{
    private const CACHE_TTL_SECONDS = 7200; // 2 hours

    public function __construct(
        private readonly Cache $cache,
        private readonly LoggerInterface $logger
    ) {}

    public function generatePropertyDescription(Property $property, string $correlationId): string
    {
        $cacheKey = "ai:description:{$property->id}";

        return $this->cache->remember($cacheKey, now()->addSeconds(self::CACHE_TTL_SECONDS), function () use ($property, $correlationId): string {
            $features = $property->features ?? [];
            $type = $property->type ?? 'property';
            $area = $property->area_sqm ?? 0;
            $price = $property->price ?? 0;

            $description = $this->buildDescription($type, $area, $price, $features);

            $this->logger->info('AI property description generated', [
                'property_id' => $property->id,
                'correlation_id' => $correlationId,
                'description_length' => strlen($description),
            ]);

            return $description;
        });
    }

    public function generatePropertyTags(Property $property, string $correlationId): array
    {
        $cacheKey = "ai:tags:{$property->id}";

        return $this->cache->tags(['realestate', 'ai', 'tags'])->remember($cacheKey, now()->addSeconds(self::CACHE_TTL_SECONDS), function () use ($property, $correlationId): array {
            $tags = [];

            $features = $property->features ?? [];
            $type = $property->type ?? '';
            $price = (float) ($property->price ?? 0);

            if ($price > 10000000) {
                $tags[] = 'luxury';
            } elseif ($price < 5000000) {
                $tags[] = 'budget_friendly';
            }

            if ($features['elevator'] ?? false) {
                $tags[] = 'elevator';
            }

            if ($features['parking'] ?? false) {
                $tags[] = 'parking';
            }

            if ($features['balcony'] ?? false) {
                $tags[] = 'balcony';
            }

            if ($features['renovated'] ?? false) {
                $tags[] = 'renovated';
            }

            if ($type === 'apartment') {
                $tags[] = 'apartment';
            } elseif ($type === 'house') {
                $tags[] = 'house';
            } elseif ($type === 'commercial') {
                $tags[] = 'commercial';
            }

            if (($features['year_built'] ?? 2020) > 2020) {
                $tags[] = 'new_construction';
            }

            $this->logger->info('AI property tags generated', [
                'property_id' => $property->id,
                'tags' => $tags,
                'correlation_id' => $correlationId,
            ]);

            return $tags;
        });
    }

    public function calculatePropertyScore(Property $property, string $correlationId): array
    {
        $cacheKey = "ai:score:{$property->id}";

        return Cemem::eKCHE_TTL_SECONDS), function () use ($property, $correlationId): array {
            $features = $property->features ?? [];
            $area = (float) ($property->area_sqm ?? 0);
            $price = (float) ($property->price ?? 0);

            $locationScore = $features['location_score'] ?? 0.7;
            $pricePerSqm = $area > 0 ? $price / $area : 0;
            $priceScore = $this->calculatePriceScore($pricePerSqm);
            $featuresScore = $this->calculateFeaturesScore($features);
            $conditionScore = $features['condition_score'] ?? 0.7;

            $overallScore = ($locationScore * 0.3) + ($priceScore * 0.3) + ($featuresScore * 0.2) + ($conditionScore * 0.2);

            $result = [
                'overall_score' => round($overallScore, 2),
                'location_score' => $locationScore,
                'price_score' => $priceScore,
                'features_score' => $featuresScore,
                'condition_score' => $conditionScore,
                'price_per_sqm' => round($pricePerSqm, 2),
                'recommendation' => $this->getRecommendation($overallScore),
                'improvements' => $this->suggestImprovements($features, $overallScore),
            ];

            $this->logger->info('AI property score calculated', [
                'property_id' => $property->id,
                'overall_score' => $overallScore,
                'correlation_id' => $correlationId,
            ]);

            return $result;
        });
    }

    public function generateSimilarProperties(Property $property, int $limit = 5, string $correlationId): array
    {
        $cacheKey = "ai:similar:{$property->id}:{$limit}";

        return $this->cache->tags(['realestate', 'ai', 'similar'])->remember($cacheKey, now()->addSeconds(self::CACHE_TTL_SECONDS), function () use ($property, $limit, $correlationId): array {
            $type = $property->type ?? '';
            $price = (float) ($property->price ?? 0);
            $priceRange = $price * 0.2; // 20% variance

            $similar = Property::where('type', $type)
                ->where('id', '!=', $property->id)
                ->where('status', 'active')
                ->whereBetween('price', [$price - $priceRange, $price + $priceRange])
                ->orderByRaw('ABS(price - ?) ASC', [$price])
                ->limit($limit)
                ->get(['id', 'uuid', 'title', 'address', 'price', 'area_sqm', 'type'])
                ->toArray();

            $this->logger->info('AI similar properties generated', [
                'property_id' => $property->id,
                'similar_count' => count($similar),
                'correlation_id' => $correlationId,
            ]);

            return $similar;
        });
    }

    public function generateInvestmentAnalysis(Property $property, string $correlationId): array
    {
        $cacheKey = "ai:investment:{$property->id}";

        return $this->cache->tags(['realestate', 'ai', 'investment'])->remember($cacheKey, now()->addSeconds(self::CACHE_TTL_SECONDS), function () use ($property, $correlationId): array {
            $price = (float) ($property->price ?? 0);
use App\Services\AI\Prompts\RealEstatePromptBuilder;
            $area = (float) ($property->area_sqm ?? 0);
            $type = $property->type ?? '';

            $monthlyRent = $this->estimateMonthlyRent($price, $area, $type);
            $annualRent = $monthlyRent * 12;
            $yield = $price > 0 ? ($annualRent / $price) * 100 : 0;

            $appreciationRate = $this->estimateAppreciationRate($type);
            $fiveYearProjection = $price * pow(1 + $appreciationRate / 100, 5);
            $tenYearProjection = $price * pow(1 + $appreciationRate / 100, 10);

            $result = [
                'purchase_price' => $price,
                'estimated_monthly_rent' => $monthlyRent,
                'estimated_annual_rent' => $annualRent,
                'rental_yield' => round($yield, 2),
                'appreciation_rate' => $appreciationRate,
                '5_year_projection' => round($fiveYearProjection, 2),
                '10_year_projection' => round($tenYearProjection, 2),
                'payback_period_years' => $yield > 0 ? round(100 / $yield, 1) : null,
                'risk_level' => $this->assessInvestmentRisk($yield, $appreciationRate),
                'recommendation' => $this->getInvestmentRecommendation($yield, $appreciationRate),
            ];

            $this->logger->info('AI investment analysis generated', [
                'property_id' => $property->id,
                'rental_yield' => $yield,
                'correlation_id' => $correlationId,
            ]);

            return $result;
        });
    }

    private function buildDescription(string $type, float $area, float $price, array $features): string
    {
        $typeRussian = match($type) {
            'apartment' => 'квартира',
            'house' => 'дом',
            'commercial' => 'коммерческое помещение',
            'studio' => 'студия',
            default => 'объект недвижимости',
        };

        $description = "Просторная {$typeRussian} площадью {$area} кв.м. ";

        if ($features['rooms'] ?? null) {
            $description .= "{$features['rooms']}-комнатная. ";
        }

        if ($features['floor'] ?? null && $features['total_floors'] ?? null) {
            $description .= "Расположена на {$features['floor']} этаже {$features['total_floors']}-этажного здания. ";
        }

        if ($features['renovated'] ?? false) {
            $description .= "Качественный ремонт. ";
        }

        if ($features['balcony'] ?? false) {
            $description .= "Имеется балкон. ";
        }

        if ($features['parking'] ?? false) {
            $description .= "Парковочное место. ";
        }

        if ($features['elevator'] ?? false) {
            $description .= "Лифт. ";
        }

        $description .= "Идеально подходит для комфортного проживания. ";

        if ($features['near_metro'] ?? false) {
            $description .= "Рядом с метро. ";
        }

        if ($features['near_park'] ?? false) {
            $description .= "В пешей доступности парк. ";
        }

        return $description;
    }

    private function calculatePriceScore(float $pricePerSqm): float
    {
        if ($pricePerSqm < 100000) {
            return 0.9;
        } elseif ($pricePerSqm < 150000) {
            return 0.8;
        } elseif ($pricePerSqm < 200000) {
            return 0.7;
        } elseif ($pricePerSqm < 250000) {
            return 0.6;
        } else {
            return 0.5;
        }
    }

    private function calculateFeaturesScore(array $features): float
    {
        $score = 0.5;
        $featuresCount = 0;

        $featureList = ['elevator', 'parking', 'balcony', 'renovated', 'security', 'concierge', 'gym', 'pool'];

        foreach ($featureList as $feature) {
            if ($features[$feature] ?? false) {
                $featuresCount++;
            }
        }

        $score += $featuresCount * 0.05;

        return min(1.0, $score);
    }

    private function getRecommendation(float $score): string
    {
        if ($score >= 0.85) {
            return 'excellent';
        } elseif ($score >= 0.7) {
            return 'good';
        } elseif ($score >= 0.5) {
            return 'fair';
        } else {
            return 'poor';
        }
    }

    private function suggestImprovements(array $features, float $score): array
    {
        $improvements = [];

        if (!($features['renovated'] ?? false)) {
            $improvements[] = 'Ремонт';
        }

        if (!($features['parking'] ?? false)) {
            $improvements[] = 'Парковка';
        }

        if (!($features['security'] ?? false)) {
            $improvements[] = 'Охрана';
        }

        if ($score < 0.7) {
            $improvements[] = 'Обновление интерьера';
        }

        return $improvements;
    }

    private function estimateMonthlyRent(float $price, float $area, string $type): float
    {
        $pricePerSqm = $area > 0 ? $price / $area : 0;

        $rentMultiplier = match($type) {
            'apartment' => 0.008,
            'house' => 0.006,
            'studio' => 0.01,
            'commercial' => 0.012,
            default => 0.007,
        };

        return $price * $rentMultiplier;
    }

    private function estimateAppreciationRate(string $type): float
    {
        return match($type) {
            'apartment' => 5.0,
            'house' => 4.5,
            'commercial' => 6.0,
            'studio' => 5.5,
            default => 5.0,
        };
    }

    private function assessInvestmentRisk(float $yield, float $appreciation): string
    {
        if ($yield > 8 && $appreciation > 5) {
            return 'low';
        } elseif ($yield > 5 && $appreciation > 3) {
            return 'medium';
        } else {
            return 'high';
        }
    }

    private function getInvestmentRecommendation(float $yield, float $appreciation): string
    {
        if ($yield > 8 && $appreciation > 5) {
            return 'strong_buy';
        } elseif ($yield > 5 && $appreciation > 3) {
            return 'buy';
        } elseif ($yield > 3 && $appreciation > 2) {
            return 'hold';
        } else {
            return 'avoid';
        }
    }

    public function clearPropertyCache(int $propertyId): void
    {
        $keys = [
            "ai:description:{$propertyId}",
            "ai:tags:{$propertyId}",
            "ai:score:{$propertyId}",
            "ai:similar:{$propertyId}:5",
            "ai:investment:{$propertyId}",
        ];

        foreach ($keys as $key) {
            $this->cache->forget($key);
        }

        $this->logger->info('AI property cache cleared', [
            'property_id' => $propertyId,
            'keys_cleared' => count($keys),
        ]);
    }
}
