<?php declare(strict_types=1);

namespace Modules\Fashion\Services\ML;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

final readonly class FashionColorHarmonyService
{
    private const CACHE_TTL = 7200;

    // Color harmony rules based on color theory
    private const COLOR_HARMONY_RULES = [
        'complementary' => [
            'red' => 'green',
            'blue' => 'orange',
            'yellow' => 'purple',
            'pink' => 'green',
            'black' => 'white',
            'white' => 'black',
            'beige' => 'brown',
            'brown' => 'beige',
        ],
        'analogous' => [
            'red' => ['pink', 'orange'],
            'blue' => ['purple', 'cyan'],
            'green' => ['yellow', 'teal'],
            'yellow' => ['orange', 'green'],
            'pink' => ['red', 'purple'],
        ],
        'monochromatic' => [
            'red' => ['dark_red', 'light_red'],
            'blue' => ['dark_blue', 'light_blue'],
            'black' => ['gray', 'charcoal'],
            'white' => ['cream', 'ivory'],
        ],
    ];

    // Beauty service colors mapped to fashion colors
    private const BEAUTY_TO_FASHION_MAP = [
        'nail_polish_red' => 'red',
        'nail_polish_pink' => 'pink',
        'nail_polish_nude' => 'beige',
        'nail_polish_french' => 'white',
        'nail_polish_dark' => 'black',
        'lipstick_red' => 'red',
        'lipstick_pink' => 'pink',
        'lipstick_nude' => 'beige',
        'lipstick_berry' => 'purple',
        'hair_blonde' => ['beige', 'cream', 'pastel'],
        'hair_brunette' => ['black', 'dark_blue', 'brown'],
        'hair_red' => ['green', 'neutral', 'beige'],
    ];

    /**
     * Get color harmony recommendations based on beauty service history
     */
    public function getRecommendationsFromBeautyHistory(int $userId, int $tenantId): array
    {
        $cacheKey = "fashion_color_harmony:{$tenantId}:{$userId}";

        return Cache::remember($cacheKey, Carbon::now()->addSeconds(self::CACHE_TTL), function () use ($userId, $tenantId) {
            // Get user's beauty service history
            $beautyHistory = $this->getBeautyHistory($userId, $tenantId);

            if (empty($beautyHistory)) {
                return [];
            }

            $recommendations = [];

            foreach ($beautyHistory as $service) {
                $fashionColors = $this->mapBeautyColorToFashion($service['type'], $service['color']);
                $harmoniousColors = $this->getHarmoniousColors($fashionColors);

                foreach ($harmoniousColors as $color) {
                    $products = $this->getProductsByColor($color, $tenantId);
                    
                    if (!empty($products)) {
                        $recommendations[] = [
                            'source_service' => $service['type'],
                            'source_color' => $service['color'],
                            'harmony_type' => $this->determineHarmonyType($fashionColors, $color),
                            'recommended_color' => $color,
                            'products' => array_slice($products, 0, 3),
                            'confidence' => $this->calculateConfidence($service['recency'], $service['frequency']),
                        ];
                    }
                }
            }

            // Sort by confidence and remove duplicates
            $recommendations = $this->deduplicateAndSort($recommendations);

            Log::info('Color harmony recommendations generated', [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'recommendations_count' => count($recommendations),
            ]);

            return array_slice($recommendations, 0, 10);
        });
    }

    /**
     * Get user's beauty service history
     */
    private function getBeautyHistory(int $userId, int $tenantId): array
    {
        try {
            // Query beauty appointments
            $appointments = DB::table('beauty_appointments')
                ->join('beauty_services', 'beauty_appointments.service_id', '=', 'beauty_services.id')
                ->where('beauty_appointments.user_id', $userId)
                ->where('beauty_appointments.tenant_id', $tenantId)
                ->where('beauty_appointments.status', 'completed')
                ->where('beauty_appointments.appointment_date', '>=', Carbon::now()->subDays(90))
                ->select(
                    'beauty_services.name as service_type',
                    'beauty_services.category',
                    'beauty_appointments.appointment_date',
                    DB::raw('COUNT(*) as frequency')
                )
                ->groupBy('beauty_services.name', 'beauty_services.category', 'beauty_appointments.appointment_date')
                ->orderByDesc('beauty_appointments.appointment_date')
                ->get()
                ->toArray();

            // Extract color information from service names/descriptions
            $history = [];
            foreach ($appointments as $appointment) {
                $color = $this->extractColorFromServiceName($appointment['service_type']);
                if ($color) {
                    $history[] = [
                        'type' => $appointment['category'],
                        'color' => $color,
                        'recency' => $this->calculateRecency($appointment['appointment_date']),
                        'frequency' => $appointment['frequency'],
                    ];
                }
            }

            return $history;
        } catch (\Exception $e) {
            Log::error('Failed to get beauty history', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Extract color from service name
     */
    private function extractColorFromServiceName(string $serviceName): ?string
    {
        $colors = ['red', 'pink', 'nude', 'french', 'dark', 'berry', 'blonde', 'brunette', 'black', 'white', 'beige'];
        
        foreach ($colors as $color) {
            if (stripos($serviceName, $color) !== false) {
                return $color;
            }
        }

        return null;
    }

    /**
     * Map beauty color to fashion colors
     */
    private function mapBeautyColorToFashion(string $serviceType, string $color): array|string
    {
        $key = strtolower($serviceType . '_' . $color);
        
        return self::BEAUTY_TO_FASHION_MAP[$key] ?? $color;
    }

    /**
     * Get harmonious colors based on color theory
     */
    private function getHarmoniousColors(array|string $baseColors): array
    {
        $harmonious = [];

        $colors = is_array($baseColors) ? $baseColors : [$baseColors];

        foreach ($colors as $color) {
            // Complementary colors
            if (isset(self::COLOR_HARMONY_RULES['complementary'][$color])) {
                $harmonious[] = self::COLOR_HARMONY_RULES['complementary'][$color];
            }

            // Analogous colors
            if (isset(self::COLOR_HARMONY_RULES['analogous'][$color])) {
                $analogous = self::COLOR_HARMONY_RULES['analogous'][$color];
                $harmonious = array_merge($harmonious, is_array($analogous) ? $analogous : [$analogous]);
            }

            // Monochromatic colors
            if (isset(self::COLOR_HARMONY_RULES['monochromatic'][$color])) {
                $monochromatic = self::COLOR_HARMONY_RULES['monochromatic'][$color];
                $harmonious = array_merge($harmonious, is_array($monochromatic) ? $monochromatic : [$monochromatic]);
            }
        }

        return array_unique($harmonious);
    }

    /**
     * Determine harmony type
     */
    private function determineHarmonyType(array|string $baseColors, string $targetColor): string
    {
        // Simple heuristic to determine harmony type
        foreach (self::COLOR_HARMONY_RULES as $type => $rules) {
            $colors = is_array($baseColors) ? $baseColors : [$baseColors];
            foreach ($colors as $color) {
                if (isset($rules[$color])) {
                    $ruleColors = is_array($rules[$color]) ? $rules[$color] : [$rules[$color]];
                    if (in_array($targetColor, $ruleColors)) {
                        return $type;
                    }
                }
            }
        }

        return 'neutral';
    }

    /**
     * Get products by color
     */
    private function getProductsByColor(string $color, int $tenantId): array
    {
        return DB::table('fashion_products')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where('available_stock', '>', 0)
            ->where(function ($query) use ($color) {
                $query->where('color', 'like', '%' . $color . '%')
                      ->orWhere('description', 'like', '%' . $color . '%');
            })
            ->select('id', 'name', 'image_url', 'price_b2c', 'color')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Calculate recency score (0-1, higher is more recent)
     */
    private function calculateRecency(string $date): float
    {
        $daysAgo = Carbon::parse($date)->diffInDays(Carbon::now());
        return max(0, 1 - ($daysAgo / 90));
    }

    /**
     * Calculate confidence score
     */
    private function calculateConfidence(float $recency, int $frequency): float
    {
        // Weight recency more heavily than frequency
        return round(($recency * 0.7) + (min($frequency / 10, 1) * 0.3), 2);
    }

    /**
     * Deduplicate and sort recommendations
     */
    private function deduplicateAndSort(array $recommendations): array
    {
        $unique = [];
        $seen = [];

        foreach ($recommendations as $rec) {
            $key = $rec['recommended_color'] . '_' . $rec['harmony_type'];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $rec;
            }
        }

        usort($unique, function ($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });

        return $unique;
    }

    /**
     * Get outfit suggestions for photo session
     */
    public function getPhotoSessionOutfitSuggestions(int $userId, int $tenantId, string $occasion = 'general'): array
    {
        $cacheKey = "fashion_photosession:{$tenantId}:{$userId}:{$occasion}";

        return Cache::remember($cacheKey, Carbon::now()->addSeconds(self::CACHE_TTL), function () use ($userId, $tenantId, $occasion) {
            // Get user's style preferences from history
            $stylePreferences = $this->getUserStylePreferences($userId, $tenantId);
            
            // Get color harmony recommendations
            $colorRecommendations = $this->getRecommendationsFromBeautyHistory($userId, $tenantId);
            
            // Get trending items
            $trendingItems = $this->getTrendingItems($tenantId);

            // Build outfit suggestions
            $suggestions = [];

            $outfitTemplates = $this->getOutfitTemplates($occasion);

            foreach ($outfitTemplates as $template) {
                $outfit = $this->buildOutfitFromTemplate($template, $colorRecommendations, $stylePreferences, $trendingItems, $tenantId);
                if (!empty($outfit)) {
                    $suggestions[] = $outfit;
                }
            }

            Log::info('Photo session outfit suggestions generated', [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'occasion' => $occasion,
                'suggestions_count' => count($suggestions),
            ]);

            return $suggestions;
        });
    }

    /**
     * Get user style preferences
     */
    private function getUserStylePreferences(int $userId, int $tenantId): array
    {
        return DB::table('fashion_order_items')
            ->join('fashion_orders', 'fashion_order_items.fashion_order_id', '=', 'fashion_orders.id')
            ->join('fashion_products', 'fashion_order_items.fashion_product_id', '=', 'fashion_products.id')
            ->where('fashion_orders.user_id', $userId)
            ->where('fashion_orders.tenant_id', $tenantId)
            ->where('fashion_orders.status', 'completed')
            ->select(
                'fashion_products.style',
                'fashion_products.color',
                DB::raw('COUNT(*) as purchase_count')
            )
            ->groupBy('fashion_products.style', 'fashion_products.color')
            ->orderByDesc('purchase_count')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Get trending items
     */
    private function getTrendingItems(int $tenantId): array
    {
        return DB::table('fashion_products')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where('available_stock', '>', 0)
            ->orderByDesc('trend_score')
            ->limit(20)
            ->select('id', 'name', 'image_url', 'price_b2c', 'color', 'style', 'category_id')
            ->get()
            ->toArray();
    }

    /**
     * Get outfit templates based on occasion
     */
    private function getOutfitTemplates(string $occasion): array
    {
        $templates = [
            'general' => [
                ['type' => 'top', 'required' => true],
                ['type' => 'bottom', 'required' => true],
                ['type' => 'shoes', 'required' => true],
                ['type' => 'accessory', 'required' => false],
            ],
            'formal' => [
                ['type' => 'dress', 'required' => true],
                ['type' => 'shoes', 'required' => true],
                ['type' => 'bag', 'required' => true],
                ['type' => 'jewelry', 'required' => false],
            ],
            'casual' => [
                ['type' => 'top', 'required' => true],
                ['type' => 'bottom', 'required' => true],
                ['type' => 'shoes', 'required' => true],
                ['type' => 'jacket', 'required' => false],
            ],
            'business' => [
                ['type' => 'blazer', 'required' => true],
                ['type' => 'shirt', 'required' => true],
                ['type' => 'trousers', 'required' => true],
                ['type' => 'shoes', 'required' => true],
            ],
        ];

        return $templates[$occasion] ?? $templates['general'];
    }

    /**
     * Build outfit from template
     */
    private function buildOutfitFromTemplate(array $template, array $colorRecs, array $preferences, array $trending, int $tenantId): ?array
    {
        $outfit = [];
        $usedColors = [];

        foreach ($template as $item) {
            $product = $this->selectProductForOutfit($item['type'], $colorRecs, $preferences, $trending, $usedColors, $tenantId);
            
            if ($product && ($item['required'] || !$item['required'])) {
                $outfit[] = [
                    'type' => $item['type'],
                    'product' => $product,
                    'required' => $item['required'],
                ];
                $usedColors[] = $product['color'];
            } elseif ($item['required']) {
                return null; // Missing required item
            }
        }

        return empty($outfit) ? null : $outfit;
    }

    /**
     * Select product for outfit
     */
    private function selectProductForOutfit(string $type, array $colorRecs, array $preferences, array $trending, array $usedColors, int $tenantId): ?array
    {
        // Priority: color recommendations > user preferences > trending
        $candidates = [];

        // Check color recommendations
        foreach ($colorRecs as $rec) {
            foreach ($rec['products'] as $product) {
                if ($this->matchesType($product, $type) && !in_array($product['color'], $usedColors)) {
                    $candidates[] = array_merge($product, ['score' => $rec['confidence'] + 0.3]);
                }
            }
        }

        // Check user preferences
        foreach ($preferences as $pref) {
            if ($this->matchesType($pref, $type)) {
                $candidates[] = array_merge($pref, ['score' => 0.6]);
            }
        }

        // Check trending
        foreach ($trending as $trend) {
            if ($this->matchesType($trend, $type) && !in_array($trend['color'], $usedColors)) {
                $candidates[] = array_merge($trend, ['score' => 0.4]);
            }
        }

        if (empty($candidates)) {
            return null;
        }

        // Sort by score and return best match
        usort($candidates, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return $candidates[0];
    }

    /**
     * Check if product matches type
     */
    private function matchesType(array $product, string $type): bool
    {
        // Simple matching based on product name/category
        $typeKeywords = [
            'top' => ['shirt', 'blouse', 'top', 'tee', 'sweater'],
            'bottom' => ['pants', 'trousers', 'skirt', 'jeans', 'shorts'],
            'shoes' => ['shoe', 'boot', 'sandal', 'heel', 'sneaker'],
            'dress' => ['dress'],
            'bag' => ['bag', 'purse', 'handbag'],
            'jewelry' => ['necklace', 'earring', 'bracelet', 'ring'],
            'jacket' => ['jacket', 'coat', 'blazer'],
            'blazer' => ['blazer'],
            'shirt' => ['shirt'],
            'trousers' => ['trousers', 'pants'],
            'accessory' => ['scarf', 'belt', 'hat', 'gloves'],
        ];

        $keywords = $typeKeywords[$type] ?? [];

        foreach ($keywords as $keyword) {
            if (stripos($product['name'], $keyword) !== false) {
                return true;
            }
        }

        return false;
    }
}
