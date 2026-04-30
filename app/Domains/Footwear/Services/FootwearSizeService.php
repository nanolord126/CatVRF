<?php

declare(strict_types=1);

namespace App\Domains\Footwear\Services;

use App\Domains\Footwear\Models\FootSizeChart;
use App\Domains\Footwear\Models\FootwearVariant;
use App\Shared\Application\Services\SizeRecommendationService;
use Illuminate\Support\Facades\Cache;

/**
 * FootwearSizeService — Footwear-specific size recommendations and conversions.
 *
 * Integrates with shared SizeRecommendationService and adds footwear-specific logic:
 * - Foot length and width measurements
 * - Shoe last type considerations
 * - Width recommendations (narrow, regular, wide, extra wide)
 * - Heel height adjustments
 *
 * @version 2026.1
 */
final class FootwearSizeService
{
    private const CACHE_TTL_HOURS = 24;

    public function __construct(
        private readonly SizeRecommendationService $sizeRecommendationService
    ) {}

    /**
     * Get recommended footwear size based on foot measurements.
     *
     * @param float $footLengthCm Foot length in cm
     * @param float $footWidthCm Foot width in cm
     * @param string $gender male, female, unisex, kids
     * @param string|null $brand Brand name for brand-specific charts
     * @return array|null
     */
    public function recommendSize(
        float $footLengthCm,
        float $footWidthCm,
        string $gender = 'unisex',
        ?string $brand = null
    ): ?array {
        $cacheKey = "footwear_size_recommendation:{$footLengthCm}:{$footWidthCm}:{$gender}:{$brand}";

        return Cache::remember($cacheKey, now()->addHours(self::CACHE_TTL_HOURS), function () use ($footLengthCm, $footWidthCm, $gender, $brand) {
            $recommendation = FootSizeChart::getRecommendedSizeForFootMeasurements(
                $footLengthCm,
                $footWidthCm,
                $gender,
                $brand
            );

            if (!$recommendation) {
                // Fallback to shared service
                return $this->sizeRecommendationService->recommendFootwearSize(
                    [
                        'foot_length' => ['min' => $footLengthCm - 0.5, 'max' => $footLengthCm + 0.5],
                        'foot_width' => ['min' => $footWidthCm - 0.3, 'max' => $footWidthCm + 0.3],
                    ],
                    $gender,
                    $brand
                );
            }

            return $recommendation;
        });
    }

    /**
     * Get recommended size based on foot length only.
     */
    public function recommendSizeByLength(
        float $footLengthCm,
        string $gender = 'unisex',
        ?string $brand = null
    ): ?array {
        return FootSizeChart::getRecommendedSizeForFootLength($footLengthCm, $gender, $brand);
    }

    /**
     * Convert footwear size between systems.
     *
     * @param string $fromSystem eu, us, uk, jp, cm
     * @param string $toSystem eu, us, uk, jp, cm
     * @param mixed $sizeValue Size value to convert
     * @param string $gender male, female, unisex
     * @param string|null $brand Brand name
     * @return string|null
     */
    public function convertSize(
        string $fromSystem,
        string $toSystem,
        mixed $sizeValue,
        string $gender = 'unisex',
        ?string $brand = null
    ): ?string {
        return $this->sizeRecommendationService->convertSize(
            $fromSystem,
            $toSystem,
            $sizeValue,
            'footwear',
            $gender,
            $brand
        );
    }

    /**
     * Get available sizes for a footwear product.
     *
     * @param int $productId Footwear product ID
     * @return array
     */
    public function getAvailableSizes(int $productId): array
    {
        $cacheKey = "footwear_available_sizes:{$productId}";

        return Cache::remember($cacheKey, now()->addHours(self::CACHE_TTL_HOURS), function () use ($productId) {
            return FootwearVariant::where('footwear_product_id', $productId)
                ->active()
                ->inStock()
                ->get()
                ->map(fn ($variant) => [
                    'variant_id' => $variant->id,
                    'size_eu' => $variant->size_eu,
                    'size_us' => $variant->size_us,
                    'size_uk' => $variant->size_uk,
                    'size_cm' => $variant->size_cm,
                    'width' => $variant->width,
                    'available_stock' => $variant->available_stock,
                    'price_adjustment' => $variant->price_adjustment,
                ])
                ->toArray();
        });
    }

    /**
     * Get available widths for a size.
     *
     * @param int $productId Footwear product ID
     * @param string $sizeEu EU size
     * @return array
     */
    public function getAvailableWidths(int $productId, string $sizeEu): array
    {
        return FootwearVariant::where('footwear_product_id', $productId)
            ->where('size_eu', $sizeEu)
            ->active()
            ->inStock()
            ->get()
            ->pluck('width')
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Check if size is available for product.
     *
     * @param int $productId Footwear product ID
     * @param string $sizeEu EU size
     * @param string|null $width Width (optional)
     * @return bool
     */
    public function isSizeAvailable(int $productId, string $sizeEu, ?string $width = null): bool
    {
        $query = FootwearVariant::where('footwear_product_id', $productId)
            ->where('size_eu', $sizeEu)
            ->active()
            ->inStock();

        if ($width) {
            $query->where('width', $width);
        }

        return $query->exists();
    }

    /**
     * Get size chart for brand.
     *
     * @param string $brand Brand name
     * @param string $gender male, female, unisex
     * @return FootSizeChart|null
     */
    public function getSizeChart(string $brand, string $gender = 'unisex'): ?FootSizeChart
    {
        return FootSizeChart::byBrand($brand)
            ->byGender($gender)
            ->active()
            ->first();
    }

    /**
     * Get standard size chart.
     *
     * @param string $gender male, female, unisex
     * @return FootSizeChart|null
     */
    public function getStandardSizeChart(string $gender = 'unisex'): ?FootSizeChart
    {
        return FootSizeChart::byBrand(null)
            ->byGender($gender)
            ->standard()
            ->active()
            ->first();
    }
}
