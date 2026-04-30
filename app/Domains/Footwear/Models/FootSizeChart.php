<?php

declare(strict_types=1);

namespace App\Domains\Footwear\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

final class FootSizeChart extends Model
{
    use TenantScoped;
    use SoftDeletes;

    protected $table = 'foot_size_charts';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'brand',
        'gender',
        'region',
        'size_eu',
        'size_us',
        'size_uk',
        'size_jp',
        'foot_length_min_cm',
        'foot_length_max_cm',
        'foot_width_cm',
        'recommended_for_width',
        'heel_height_cm',
        'toe_box_width',
        'arch_support_level',
        'is_standard',
        'correlation_id',
        'metadata',
    ];

    protected $casts = [
        'foot_length_min_cm' => 'float',
        'foot_length_max_cm' => 'float',
        'foot_width_cm' => 'float',
        'heel_height_cm' => 'float',
        'toe_box_width' => 'float',
        'is_standard' => 'boolean',
        'metadata' => 'json',
    ];

    protected $hidden = [
        'correlation_id',
        'metadata',
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

    public const WIDTH_NARROW = 'narrow';
    public const WIDTH_REGULAR = 'regular';
    public const WIDTH_WIDE = 'wide';
    public const WIDTH_EXTRA_WIDE = 'extra_wide';

    public const ARCH_SUPPORT_LOW = 'low';
    public const ARCH_SUPPORT_MEDIUM = 'medium';
    public const ARCH_SUPPORT_HIGH = 'high';

    public function scopeByBrand(Builder $query, string $brand): Builder
    {
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

    public function scopeStandard(Builder $query): Builder
    {
        return $query->where('is_standard', true);
    }

    public function scopeByWidth(Builder $query, string $width): Builder
    {
        return $query->where('recommended_for_width', $width);
    }

    public function getRecommendedSizeForFootLength(float $footLengthCm, string $gender = 'unisex', ?string $brand = null): ?array
    {
        $cacheKey = "foot_size_chart:recommendation:{$footLengthCm}:{$gender}:{$brand}";

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($footLengthCm, $gender, $brand) {
            $query = self::where('gender', $gender)
                ->where('foot_length_min_cm', '<=', $footLengthCm)
                ->where('foot_length_max_cm', '>=', $footLengthCm);

            if ($brand) {
                $query->where('brand', $brand);
            }

            $chart = $query->orderByRaw('ABS(foot_length_min_cm - ?) ASC', [$footLengthCm])
                ->first();

            if (!$chart) {
                return null;
            }

            return [
                'size_eu' => $chart->size_eu,
                'size_us' => $chart->size_us,
                'size_uk' => $chart->size_uk,
                'size_jp' => $chart->size_jp,
                'foot_length_min_cm' => $chart->foot_length_min_cm,
                'foot_length_max_cm' => $chart->foot_length_max_cm,
                'recommended_width' => $chart->recommended_for_width,
                'brand' => $chart->brand,
                'confidence' => $this->calculateConfidence($footLengthCm, $chart),
            ];
        });
    }

    public function getRecommendedSizeForFootMeasurements(float $footLengthCm, float $footWidthCm, string $gender = 'unisex', ?string $brand = null): ?array
    {
        $cacheKey = "foot_size_chart:recommendation:{$footLengthCm}:{$footWidthCm}:{$gender}:{$brand}";

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($footLengthCm, $footWidthCm, $gender, $brand) {
            $query = self::where('gender', $gender)
                ->where('foot_length_min_cm', '<=', $footLengthCm)
                ->where('foot_length_max_cm', '>=', $footLengthCm);

            if ($brand) {
                $query->where('brand', $brand);
            }

            $charts = $query->orderByRaw('ABS(foot_length_min_cm - ?) ASC', [$footLengthCm])
                ->get();

            if ($charts->isEmpty()) {
                return null;
            }

            $bestMatch = null;
            $bestScore = -1;

            foreach ($charts as $chart) {
                $lengthScore = $this->calculateLengthScore($footLengthCm, $chart);
                $widthScore = $this->calculateWidthScore($footWidthCm, $chart);
                $totalScore = ($lengthScore * 0.7) + ($widthScore * 0.3);

                if ($totalScore > $bestScore) {
                    $bestScore = $totalScore;
                    $bestMatch = $chart;
                }
            }

            if (!$bestMatch) {
                return null;
            }

            $widthRecommendation = $this->determineWidthRecommendation($footWidthCm, $bestMatch);

            return [
                'size_eu' => $bestMatch->size_eu,
                'size_us' => $bestMatch->size_us,
                'size_uk' => $bestMatch->size_uk,
                'size_jp' => $bestMatch->size_jp,
                'recommended_width' => $widthRecommendation,
                'brand' => $bestMatch->brand,
                'confidence' => $bestScore,
                'foot_length_fit' => $this->getFitDescription($footLengthCm, $bestMatch),
                'foot_width_fit' => $this->getWidthFitDescription($footWidthCm, $bestMatch),
            ];
        });
    }

    private function calculateConfidence(float $footLengthCm, self $chart): float
    {
        $range = $chart->foot_length_max_cm - $chart->foot_length_min_cm;
        $position = ($footLengthCm - $chart->foot_length_min_cm) / $range;

        if ($position < 0.2 || $position > 0.8) {
            return 0.7;
        } elseif ($position < 0.4 || $position > 0.6) {
            return 0.85;
        } else {
            return 0.95;
        }
    }

    private function calculateLengthScore(float $footLengthCm, self $chart): float
    {
        $midpoint = ($chart->foot_length_min_cm + $chart->foot_length_max_cm) / 2;
        $deviation = abs($footLengthCm - $midpoint);
        $range = $chart->foot_length_max_cm - $chart->foot_length_min_cm;

        return max(0, 1 - ($deviation / ($range / 2)));
    }

    private function calculateWidthScore(float $footWidthCm, self $chart): float
    {
        $deviation = abs($footWidthCm - $chart->foot_width_cm);
        return max(0, 1 - ($deviation / 2));
    }

    private function determineWidthRecommendation(float $footWidthCm, self $chart): string
    {
        if ($footWidthCm < $chart->foot_width_cm - 0.5) {
            return self::WIDTH_NARROW;
        } elseif ($footWidthCm > $chart->foot_width_cm + 0.5) {
            return self::WIDTH_WIDE;
        } elseif ($footWidthCm > $chart->foot_width_cm + 1.0) {
            return self::WIDTH_EXTRA_WIDE;
        } else {
            return self::WIDTH_REGULAR;
        }
    }

    private function getFitDescription(float $footLengthCm, self $chart): string
    {
        $confidence = $this->calculateConfidence($footLengthCm, $chart);

        if ($confidence >= 0.95) {
            return 'perfect_fit';
        } elseif ($confidence >= 0.85) {
            return 'good_fit';
        } else {
            return 'acceptable_fit';
        }
    }

    private function getWidthFitDescription(float $footWidthCm, self $chart): string
    {
        $deviation = abs($footWidthCm - $chart->foot_width_cm);

        if ($deviation < 0.3) {
            return 'perfect_width';
        } elseif ($deviation < 0.6) {
            return 'good_width';
        } else {
            return 'consider_width_adjustment';
        }
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = tenant()->id ?? auth()->user()?->tenant_id;
            if ($tenantId) {
                $builder->where('tenant_id', $tenantId);
            }
        });

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
            if (empty($model->correlation_id)) {
                $model->correlation_id = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }
}
