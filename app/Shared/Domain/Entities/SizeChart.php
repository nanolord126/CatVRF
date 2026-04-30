<?php

declare(strict_types=1);

namespace App\Shared\Domain\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * SizeChart — Shared domain entity for size conversions (Fashion & Footwear).
 *
 * Supports size systems: EU, US, UK, JP, International (XS/S/M/L/XL).
 * Brand-specific and region-specific size charts are supported.
 * Category field distinguishes between 'fashion', 'footwear', or universal (null).
 *
 * @property string $uuid
 * @property string $brand Brand name for brand-specific charts
 * @property string $gender (male, female, unisex, kids)
 * @property string $region (eu, us, uk, jp, cn, ru)
 * @property string $size_system (eu, us, uk, jp, international)
 * @property array $size_mappings Size conversion mappings
 * @property array $measurements Body measurements in cm
 * @property string|null $category (fashion, footwear, or null for universal)
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
final class SizeChart extends Model
{
    protected $table = 'shared_size_charts';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'uuid',
        'brand',
        'gender',
        'region',
        'size_system',
        'size_mappings',
        'measurements',
        'category',
        'is_active',
    ];

    protected $casts = [
        'size_mappings' => 'json',
        'measurements' => 'json',
        'is_active' => 'boolean',
    ];

    public const GENDER_MALE = 'male';
    public const GENDER_FEMALE = 'female';
    public const GENDER_UNISEX = 'unisex';
    public const GENDER_KIDS = 'kids';

    public const REGION_EU = 'eu';
    public const REGION_US = 'us';
    public const REGION_UK = 'uk';
    public const REGION_JP = 'jp';
    public const REGION_CN = 'cn';
    public const REGION_RU = 'ru';

    public const SIZE_SYSTEM_EU = 'eu';
    public const SIZE_SYSTEM_US = 'us';
    public const SIZE_SYSTEM_UK = 'uk';
    public const SIZE_SYSTEM_JP = 'jp';
    public const SIZE_SYSTEM_INTERNATIONAL = 'international';

    public const CATEGORY_FASHION = 'fashion';
    public const CATEGORY_FOOTWEAR = 'footwear';

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByBrand(Builder $query, ?string $brand): Builder
    {
        if ($brand === null) {
            return $query->whereNull('brand');
        }
        return $query->where('brand', $brand);
    }

    public function scopeByGender(Builder $query, string $gender): Builder
    {
        return $query->where('gender', $gender);
    }

    public function scopeByRegion(Builder $query, string $region): Builder
    {
        return $query->where('region', $region);
    }

    public function scopeBySizeSystem(Builder $query, string $sizeSystem): Builder
    {
        return $query->where('size_system', $sizeSystem);
    }

    public function scopeByCategory(Builder $query, ?string $category): Builder
    {
        if ($category === null) {
            return $query->whereNull('category');
        }
        return $query->where('category', $category);
    }

    public function scopeFashion(Builder $query): Builder
    {
        return $query->where('category', self::CATEGORY_FASHION);
    }

    public function scopeFootwear(Builder $query): Builder
    {
        return $query->where('category', self::CATEGORY_FOOTWEAR);
    }

    public function scopeUniversal(Builder $query): Builder
    {
        return $query->whereNull('category');
    }

    /**
     * Convert size from one system to another.
     */
    public function convertSize(string $fromSystem, string $toSystem, mixed $sizeValue): ?string
    {
        $mappings = $this->size_mappings;

        if (!isset($mappings[$fromSystem][$sizeValue])) {
            return null;
        }

        return $mappings[$fromSystem][$sizeValue][$toSystem] ?? null;
    }

    /**
     * Get recommended size based on body measurements.
     */
    public function getRecommendedSize(
        array $measurements,
        string $gender = 'unisex',
        ?string $brand = null,
        ?string $category = null
    ): ?array {
        $cacheKey = "size_chart:recommendation:" . md5(json_encode($measurements)) . ":{$gender}:{$brand}:{$category}";

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($measurements, $gender, $brand, $category) {
            $query = self::where('gender', $gender)
                ->active();

            if ($brand) {
                $query->where('brand', $brand);
            } else {
                $query->whereNull('brand');
            }

            if ($category) {
                $query->where('category', $category);
            }

            $charts = $query->get();

            if ($charts->isEmpty()) {
                // Fallback to universal chart
                if ($category) {
                    return $this->getRecommendedSize($measurements, $gender, $brand, null);
                }
                return null;
            }

            $bestMatch = null;
            $bestScore = -1;

            foreach ($charts as $chart) {
                $score = $this->calculateMatchScore($measurements, $chart->measurements);

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestMatch = $chart;
                }
            }

            if (!$bestMatch) {
                return null;
            }

            return [
                'uuid' => $bestMatch->uuid,
                'brand' => $bestMatch->brand,
                'size_mappings' => $bestMatch->size_mappings,
                'measurements' => $bestMatch->measurements,
                'confidence' => $bestScore,
            ];
        });
    }

    /**
     * Calculate match score between user measurements and chart measurements.
     */
    private function calculateMatchScore(array $userMeasurements, array $chartMeasurements): float
    {
        $totalScore = 0;
        $count = 0;

        foreach ($userMeasurements as $key => $userValue) {
            if (!isset($chartMeasurements[$key])) {
                continue;
            }

            $chartRange = $chartMeasurements[$key];
            if (!is_array($chartRange) || !isset($chartRange['min']) || !isset($chartRange['max'])) {
                continue;
            }

            $min = $chartRange['min'];
            $max = $chartRange['max'];

            if ($userValue < $min || $userValue > $max) {
                // Outside range, penalize heavily
                $score = max(0, 1 - (abs($userValue - ($min + $max) / 2) / (($max - $min) / 2)));
            } else {
                // Within range, calculate position
                $midpoint = ($min + $max) / 2;
                $range = $max - $min;
                $deviation = abs($userValue - $midpoint);
                $score = max(0, 1 - ($deviation / ($range / 2)));
            }

            $totalScore += $score;
            $count++;
        }

        return $count > 0 ? $totalScore / $count : 0;
    }

    /**
     * Get size chart for specific brand, gender, and category.
     */
    public static function getChart(string $brand, string $gender, string $category): ?self
    {
        $cacheKey = "size_chart:{$brand}:{$gender}:{$category}";

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($brand, $gender, $category) {
            return self::byBrand($brand)
                ->byGender($gender)
                ->byCategory($category)
                ->active()
                ->first();
        });
    }

    /**
     * Get standard (non-brand) size chart.
     */
    public static function getStandardChart(string $gender, string $category): ?self
    {
        $cacheKey = "size_chart:standard:{$gender}:{$category}";

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($gender, $category) {
            return self::byBrand(null)
                ->byGender($gender)
                ->byCategory($category)
                ->active()
                ->first();
        });
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });

        static::updated(function ($model) {
            Cache::tags(['size_charts'])->flush();
        });

        static::deleted(function ($model) {
            Cache::tags(['size_charts'])->flush();
        });
    }
}
