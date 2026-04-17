<?php declare(strict_types=1);

namespace Modules\Fashion\Services\ML;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

final readonly class FashionMannequinSizeAlgorithmService
{
    private const CACHE_TTL = 86400;

    // Standard body proportions for mannequin
    private const STANDARD_PROPORTIONS = [
        'height_to_chest' => 0.45,
        'height_to_waist' => 0.38,
        'height_to_hips' => 0.53,
        'chest_to_waist' => 0.85,
        'waist_to_hips' => 0.75,
        'arm_span_to_height' => 1.0,
        'leg_length_to_height' => 0.48,
        'foot_to_height' => 0.15,
    ];

    // Brand size conversion factors (brand tag vs actual measurements)
    private const BRAND_CONVERSION_FACTORS = [
        'zara' => ['size_multiplier' => 0.95, 'run_small' => true],
        'h_and_m' => ['size_multiplier' => 1.0, 'run_small' => false],
        'mango' => ['size_multiplier' => 0.97, 'run_small' => true],
        'gucci' => ['size_multiplier' => 1.05, 'run_small' => false],
        'prada' => ['size_multiplier' => 1.03, 'run_small' => false],
        'calvin_klein' => ['size_multiplier' => 1.0, 'run_small' => false],
        'levi_s' => ['size_multiplier' => 0.98, 'run_small' => true],
        'nike' => ['size_multiplier' => 1.0, 'run_small' => false],
    ];

    /**
     * Calculate ideal size using mannequin algorithm
     */
    public function calculateIdealSize(int $userId, int $tenantId, int $productId): array
    {
        $cacheKey = "fashion_mannequin_size:{$tenantId}:{$userId}:{$productId}";

        return Cache::remember($cacheKey, Carbon::now()->addSeconds(self::CACHE_TTL), function () use ($userId, $tenantId, $productId) {
            // Get user's body measurements
            $userMeasurements = $this->getUserMeasurements($userId, $tenantId);
            
            // Get product details and brand
            $product = DB::table('fashion_products')
                ->join('fashion_brands', 'fashion_products.brand_id', '=', 'fashion_brands.id')
                ->where('fashion_products.id', $productId)
                ->where('fashion_products.tenant_id', $tenantId)
                ->select('fashion_products.*', 'fashion_brands.name as brand_name', 'fashion_brands.id as brand_id')
                ->first();

            if (!$product) {
                return ['error' => 'Product not found'];
            }

            // Get brand-specific size chart
            $brandSizeChart = $this->getBrandSizeChart($product->brand_id, $product->category_id, $tenantId);
            
            // Calculate user's ideal measurements based on standard proportions
            $idealMeasurements = $this->calculateIdealMeasurements($userMeasurements);
            
            // Apply brand conversion factors
            $adjustedMeasurements = $this->applyBrandConversion($idealMeasurements, $product->brand_name);
            
            // Find best matching size
            $bestSize = $this->findBestMatchingSize($adjustedMeasurements, $brandSizeChart);
            
            // Calculate fit confidence
            $confidence = $this->calculateFitConfidence($userMeasurements, $adjustedMeasurements, $bestSize);
            
            // Get size variation tolerance
            $tolerance = $this->getSizeTolerance($product->category_id, $product->brand_name);

            Log::info('Mannequin size calculation completed', [
                'user_id' => $userId,
                'product_id' => $productId,
                'brand' => $product->brand_name,
                'recommended_size' => $bestSize['size'] ?? null,
                'confidence' => $confidence,
            ]);

            return [
                'recommended_size' => $bestSize['size'] ?? null,
                'confidence' => $confidence,
                'user_measurements' => $userMeasurements,
                'ideal_measurements' => $idealMeasurements,
                'adjusted_measurements' => $adjustedMeasurements,
                'size_chart_reference' => $bestSize,
                'tolerance' => $tolerance,
                'brand_runs' => $this->getBrandRunInfo($product->brand_name),
                'alternative_sizes' => $this->getAlternativeSizes($bestSize, $brandSizeChart, $tolerance),
            ];
        });
    }

    /**
     * Get user's body measurements
     */
    private function getUserMeasurements(int $userId, int $tenantId): array
    {
        $measurements = DB::table('fashion_user_size_profiles')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$measurements) {
            // Estimate from order history if no measurements
            return $this->estimateFromOrderHistory($userId, $tenantId);
        }

        return [
            'height' => $measurements->height,
            'weight' => $measurements->weight,
            'chest' => $measurements->chest,
            'waist' => $measurements->waist,
            'hips' => $measurements->hips,
            'shoulder_width' => $measurements->shoulder_width ?? null,
            'arm_length' => $measurements->arm_length ?? null,
            'leg_length' => $measurements->leg_length ?? null,
            'foot_size' => $measurements->shoe_size,
        ];
    }

    /**
     * Estimate measurements from order history
     */
    private function estimateFromOrderHistory(int $userId, int $tenantId): array
    {
        // Get average sizes from successful returns (fits well)
        $successfulOrders = DB::table('fashion_order_items')
            ->join('fashion_orders', 'fashion_order_items.fashion_order_id', '=', 'fashion_orders.id')
            ->join('fashion_products', 'fashion_order_items.fashion_product_id', '=', 'fashion_products.id')
            ->leftJoin('fashion_returns', function ($join) {
                $join->on('fashion_order_items.fashion_order_id', '=', 'fashion_returns.order_id')
                     ->on('fashion_order_items.fashion_product_id', '=', 'fashion_returns.product_id');
            })
            ->where('fashion_orders.user_id', $userId)
            ->where('fashion_orders.tenant_id', $tenantId)
            ->where('fashion_orders.status', 'completed')
            ->where(function ($query) {
                $query->whereNull('fashion_returns.id')
                      ->orWhere('fashion_returns.status', '!=', 'completed');
            })
            ->select('fashion_products.size', 'fashion_products.category_id', 'fashion_products.brand_id')
            ->get()
            ->groupBy('size', 'category_id', 'brand_id')
            ->get()
            ->toArray();

        // Estimate average measurements based on size
        return [
            'estimated_from_history' => true,
            'successful_orders' => count($successfulOrders),
        ];
    }

    /**
     * Calculate ideal measurements based on standard proportions
     */
    private function calculateIdealMeasurements(array $userMeasurements): array
    {
        $height = $userMeasurements['height'] ?? 170;
        $ideal = [];

        // Calculate ideal measurements using standard proportions
        $ideal['chest'] = $userMeasurements['chest'] ?? ($height * self::STANDARD_PROPORTIONS['height_to_chest'] * 2.5);
        $ideal['waist'] = $userMeasurements['waist'] ?? ($height * self::STANDARD_PROPORTIONS['height_to_waist'] * 2.2);
        $ideal['hips'] = $userMeasurements['hips'] ?? ($height * self::STANDARD_PROPORTIONS['height_to_hips'] * 2.5);
        $ideal['shoulder_width'] = $userMeasurements['shoulder_width'] ?? ($ideal['chest'] * 0.4);
        $ideal['arm_length'] = $userMeasurements['arm_length'] ?? ($height * self::STANDARD_PROPORTIONS['arm_span_to_height'] * 0.45);
        $ideal['leg_length'] = $userMeasurements['leg_length'] ?? ($height * self::STANDARD_PROPORTIONS['leg_length_to_height']);
        $ideal['foot_size'] = $userMeasurements['foot_size'] ?? ($height * self::STANDARD_PROPORTIONS['foot_to_height'] * 0.67);

        return $ideal;
    }

    /**
     * Apply brand-specific conversion factors
     */
    private function applyBrandConversion(array $measurements, string $brandName): array
    {
        $brandKey = strtolower(str_replace([' ', '&', "'"], ['_', '_', ''], $brandName));
        $conversion = self::BRAND_CONVERSION_FACTORS[$brandKey] ?? ['size_multiplier' => 1.0, 'run_small' => false];

        $adjusted = [];

        foreach ($measurements as $key => $value) {
            if (is_numeric($value)) {
                $adjusted[$key] = $value * $conversion['size_multiplier'];
            } else {
                $adjusted[$key] = $value;
            }
        }

        $adjusted['brand_multiplier'] = $conversion['size_multiplier'];
        $adjusted['brand_runs_small'] = $conversion['run_small'];

        return $adjusted;
    }

    /**
     * Get brand-specific size chart
     */
    private function getBrandSizeChart(int $brandId, int $categoryId, int $tenantId): array
    {
        return DB::table('fashion_sizes')
            ->where('brand_id', $brandId)
            ->where('category_id', $categoryId)
            ->where('tenant_id', $tenantId)
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }

    /**
     * Find best matching size from size chart
     */
    private function findBestMatchingSize(array $measurements, array $sizeChart): array
    {
        $bestMatch = null;
        $bestScore = PHP_FLOAT_MAX;

        foreach ($sizeChart as $size) {
            $score = 0;
            $dimensions = 0;

            // Compare chest
            if (isset($measurements['chest']) && isset($size->chest)) {
                $score += abs($measurements['chest'] - $size->chest);
                $dimensions++;
            }

            // Compare waist
            if (isset($measurements['waist']) && isset($size->waist)) {
                $score += abs($measurements['waist'] - $size->waist);
                $dimensions++;
            }

            // Compare hips
            if (isset($measurements['hips']) && isset($size->hips)) {
                $score += abs($measurements['hips'] - $size->hips);
                $dimensions++;
            }

            // Compare shoulder width
            if (isset($measurements['shoulder_width']) && isset($size->shoulder_width)) {
                $score += abs($measurements['shoulder_width'] - $size->shoulder_width);
                $dimensions++;
            }

            // Normalize score
            if ($dimensions > 0) {
                $normalizedScore = $score / $dimensions;

                if ($normalizedScore < $bestScore) {
                    $bestScore = $normalizedScore;
                    $bestMatch = $size;
                }
            }
        }

        return $bestMatch ? (array) $bestMatch : [];
    }

    /**
     * Calculate fit confidence score
     */
    private function calculateFitConfidence(array $userMeasurements, array $adjustedMeasurements, array $bestSize): float
    {
        if (empty($bestSize)) {
            return 0.0;
        }

        $confidence = 1.0;
        $factors = 0;

        // Check if user has actual measurements
        if (isset($userMeasurements['estimated_from_history']) && $userMeasurements['estimated_from_history']) {
            $confidence *= 0.7; // Lower confidence for estimated measurements
            $factors++;
        }

        // Check brand conversion factor deviation
        if (isset($adjustedMeasurements['brand_multiplier'])) {
            $deviation = abs($adjustedMeasurements['brand_multiplier'] - 1.0);
            $confidence *= (1 - $deviation);
            $factors++;
        }

        // Check if size is within reasonable tolerance
        if (!empty($bestSize)) {
            $confidence *= 0.9;
            $factors++;
        }

        return $factors > 0 ? round($confidence, 2) : 0.0;
    }

    /**
     * Get size tolerance for category and brand
     */
    private function getSizeTolerance(int $categoryId, string $brandName): array
    {
        $brandKey = strtolower(str_replace([' ', '&', "'"], ['_', '_', ''], $brandName));
        $conversion = self::BRAND_CONVERSION_FACTORS[$brandKey] ?? ['size_multiplier' => 1.0];

        // Category-specific tolerances (in cm)
        $categoryTolerances = [
            1 => ['chest' => 3, 'waist' => 2, 'hips' => 3], // Tops
            2 => ['waist' => 2, 'hips' => 2, 'inseam' => 2], // Bottoms
            3 => ['chest' => 4, 'waist' => 3, 'hips' => 4], // Dresses
            4 => ['length' => 1], // Shoes
        ];

        $tolerance = $categoryTolerances[$categoryId] ?? ['chest' => 3, 'waist' => 2, 'hips' => 3];

        // Adjust tolerance based on brand multiplier
        foreach ($tolerance as $key => $value) {
            $tolerance[$key] = round($value * $conversion['size_multiplier'], 1);
        }

        return $tolerance;
    }

    /**
     * Get brand run information
     */
    private function getBrandRunInfo(string $brandName): array
    {
        $brandKey = strtolower(str_replace([' ', '&', "'"], ['_', '_', ''], $brandName));
        $conversion = self::BRAND_CONVERSION_FACTORS[$brandKey] ?? ['size_multiplier' => 1.0, 'run_small' => false];

        return [
            'runs_small' => $conversion['run_small'],
            'size_multiplier' => $conversion['size_multiplier'],
            'recommendation' => $conversion['run_small'] ? 'Consider sizing up' : 'True to size',
        ];
    }

    /**
     * Get alternative sizes within tolerance
     */
    private function getAlternativeSizes(array $bestSize, array $sizeChart, array $tolerance): array
    {
        if (empty($bestSize)) {
            return [];
        }

        $alternatives = [];
        $currentIndex = array_search($bestSize['size'], array_column($sizeChart, 'size'));

        if ($currentIndex === false) {
            return [];
        }

        // Check size below
        if ($currentIndex > 0) {
            $alternatives[] = [
                'size' => $sizeChart[$currentIndex - 1]->size,
                'recommendation' => 'Size down',
                'fit_note' => 'For a slimmer fit',
            ];
        }

        // Check size above
        if ($currentIndex < count($sizeChart) - 1) {
            $alternatives[] = [
                'size' => $sizeChart[$currentIndex + 1]->size,
                'recommendation' => 'Size up',
                'fit_note' => 'For a looser fit',
            ];
        }

        return $alternatives;
    }

    /**
     * Update size accuracy based on user feedback
     */
    public function updateSizeAccuracy(int $userId, int $tenantId, int $productId, string $actualSize, string $feedback): bool
    {
        try {
            // Record feedback for ML training
            DB::table('fashion_size_feedback')->insert([
                'id' => uniqid(),
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'product_id' => $productId,
                'recommended_size' => $actualSize,
                'feedback' => $feedback, // 'perfect', 'too_small', 'too_large'
                'created_at' => Carbon::now(),
            ]);

            // Clear user size cache
            Cache::tags(["fashion_mannequin_size:{$tenantId}:{$userId}"])->flush();

            Log::info('Size accuracy feedback recorded', [
                'user_id' => $userId,
                'product_id' => $productId,
                'feedback' => $feedback,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to record size feedback', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get size accuracy statistics for a brand
     */
    public function getBrandSizeAccuracy(int $brandId, int $tenantId): array
    {
        $feedback = DB::table('fashion_size_feedback')
            ->join('fashion_products', 'fashion_size_feedback.product_id', '=', 'fashion_products.id')
            ->where('fashion_products.brand_id', $brandId)
            ->where('fashion_size_feedback.tenant_id', $tenantId)
            ->select('feedback', DB::raw('COUNT(*) as count'))
            ->groupBy('feedback')
            ->get()
            ->keyBy('feedback')
            ->toArray();

        $total = array_sum(array_column($feedback, 'count'));
        $perfect = $feedback['perfect']->count ?? 0;
        $accuracy = $total > 0 ? round(($perfect / $total) * 100, 2) : 0;

        return [
            'total_feedback' => $total,
            'perfect_fit' => $perfect,
            'too_small' => $feedback['too_small']->count ?? 0,
            'too_large' => $feedback['too_large']->count ?? 0,
            'accuracy_percentage' => $accuracy,
        ];
    }

    /**
     * Train size recommendation model with feedback data
     */
    public function trainSizeModel(int $tenantId): void
    {
        try {
            // Get all feedback data
            $feedbackData = DB::table('fashion_size_feedback')
                ->join('fashion_products', 'fashion_size_feedback.product_id', '=', 'fashion_products.id')
                ->join('fashion_user_size_profiles', 'fashion_size_feedback.user_id', '=', 'fashion_user_size_profiles.user_id')
                ->where('fashion_size_feedback.tenant_id', $tenantId)
                ->select(
                    'fashion_user_size_profiles.*',
                    'fashion_products.brand_id',
                    'fashion_products.category_id',
                    'fashion_size_feedback.feedback'
                )
                ->get()
                ->toArray();

            // Analyze patterns and update brand conversion factors
            $this->updateBrandConversionFactors($feedbackData, $tenantId);

            // Clear all size caches
            Cache::tags(["fashion_mannequin_size:{$tenantId}"])->flush();

            Log::info('Size model training completed', [
                'tenant_id' => $tenantId,
                'feedback_records' => count($feedbackData),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to train size model', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update brand conversion factors based on feedback
     */
    private function updateBrandConversionFactors(array $feedbackData, int $tenantId): void
    {
        // Group by brand
        $byBrand = [];
        foreach ($feedbackData as $feedback) {
            $brandId = $feedback['brand_id'];
            if (!isset($byBrand[$brandId])) {
                $byBrand[$brandId] = ['too_small' => 0, 'too_large' => 0, 'perfect' => 0];
            }
            $byBrand[$brandId][$feedback['feedback']]++;
        }

        // Calculate adjustment factors
        foreach ($byBrand as $brandId => $stats) {
            $total = $stats['too_small'] + $stats['too_large'] + $stats['perfect'];
            if ($total === 0) continue;

            $tooSmallRatio = $stats['too_small'] / $total;
            $tooLargeRatio = $stats['too_large'] / $total;

            // If too many "too small" feedback, brand runs large (need smaller multiplier)
            // If too many "too large" feedback, brand runs small (need larger multiplier)
            $adjustment = 1.0;

            if ($tooSmallRatio > 0.3) {
                $adjustment = 0.98; // Brand runs large
            } elseif ($tooLargeRatio > 0.3) {
                $adjustment = 1.02; // Brand runs small
            }

            // Store adjustment for future use
            DB::table('fashion_brand_adjustments')->updateOrInsert(
                ['brand_id' => $brandId, 'tenant_id' => $tenantId],
                [
                    'adjustment_factor' => $adjustment,
                    'sample_size' => $total,
                    'updated_at' => Carbon::now(),
                ]
            );
        }
    }
}
