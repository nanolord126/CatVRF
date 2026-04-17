<?php declare(strict_types=1);

namespace Modules\Fashion\Services\ML;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

final readonly class FashionCrossVerticalRecommendationService
{
    private const CACHE_TTL = 3600;

    public function getBeautyToFashionRecommendations(int $userId, int $tenantId, int $limit = 10): array
    {
        $cacheKey = "fashion_cross_vertical:{$tenantId}:{$userId}";

        return Cache::remember($cacheKey, Carbon::now()->addSeconds(self::CACHE_TTL), function () use ($userId, $tenantId, $limit) {
            $recommendations = [];
            $beautyServices = $this->getRecentBeautyServices($userId, $tenantId);
            
            foreach ($beautyServices as $service) {
                $fashionRecs = $this->generateFashionRecommendations($service, $tenantId);
                $recommendations = array_merge($recommendations, $fashionRecs);
            }

            $recommendations = $this->sortAndLimit($recommendations, $limit);

            Log::info('Cross-vertical recommendations generated', [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'recommendations_count' => count($recommendations),
            ]);

            return $recommendations;
        });
    }

    private function getRecentBeautyServices(int $userId, int $tenantId): array
    {
        try {
            $services = DB::table('beauty_appointments')
                ->join('beauty_services', 'beauty_appointments.service_id', '=', 'beauty_services.id')
                ->where('beauty_appointments.user_id', $userId)
                ->where('beauty_appointments.tenant_id', $tenantId)
                ->where('beauty_appointments.status', 'completed')
                ->where('beauty_appointments.appointment_date', '>=', Carbon::now()->subDays(60))
                ->select('beauty_services.name as service_name', 'beauty_services.category', 'beauty_appointments.appointment_date')
                ->orderByDesc('beauty_appointments.appointment_date')
                ->get()
                ->toArray();

            return $services;
        } catch (\Exception $e) {
            Log::error('Failed to get beauty services', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    private function generateFashionRecommendations(array $service, int $tenantId): array
    {
        $recommendations = [];

        switch ($service['category']) {
            case 'manicure':
                $recommendations = array_merge($recommendations, $this->getManicureBasedRecommendations($service, $tenantId));
                break;
            case 'pedicure':
                $recommendations = array_merge($recommendations, $this->getPedicureBasedRecommendations($service, $tenantId));
                break;
            case 'hair':
                $recommendations = array_merge($recommendations, $this->getHairBasedRecommendations($service, $tenantId));
                break;
            case 'makeup':
                $recommendations = array_merge($recommendations, $this->getMakeupBasedRecommendations($service, $tenantId));
                break;
        }

        return $recommendations;
    }

    private function getManicureBasedRecommendations(array $service, int $tenantId): array
    {
        $color = $this->extractColor($service['service_name']);
        
        if (!$color) {
            return [];
        }

        $products = DB::table('fashion_products')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where('available_stock', '>', 0)
            ->whereIn('category_id', [7, 8])
            ->where(function ($query) use ($color) {
                $query->where('color', 'like', '%' . $color . '%')
                      ->orWhere('description', 'like', '%' . $color . '%');
            })
            ->select('id', 'name', 'image_url', 'price_b2c', 'color', 'category_id')
            ->limit(5)
            ->get()
            ->toArray();

        return array_map(function ($product) use ($service, $color) {
            return [
                'product' => $product,
                'source' => 'manicure',
                'reason' => "Matches your {$color} nail color",
                'relevance_score' => 0.85,
                'service_date' => $service['appointment_date'],
            ];
        }, $products);
    }

    private function getPedicureBasedRecommendations(array $service, int $tenantId): array
    {
        $neutralColors = ['nude', 'beige', 'black', 'white', 'tan', 'gold', 'silver'];
        
        $products = DB::table('fashion_products')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where('available_stock', '>', 0)
            ->where('category_id', 6)
            ->whereIn('color', $neutralColors)
            ->select('id', 'name', 'image_url', 'price_b2c', 'color', 'category_id')
            ->limit(5)
            ->get()
            ->toArray();

        return array_map(function ($product) use ($service) {
            return [
                'product' => $product,
                'source' => 'pedicure',
                'reason' => 'Complements your recent pedicure',
                'relevance_score' => 0.80,
                'service_date' => $service['appointment_date'],
            ];
        }, $products);
    }

    private function getHairBasedRecommendations(array $service, int $tenantId): array
    {
        $hairType = $this->extractHairType($service['service_name']);
        $recommendations = [];

        if ($hairType) {
            $complementaryColors = $this->getComplementaryColorsForHair($hairType);
            
            $products = DB::table('fashion_products')
                ->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->where('available_stock', '>', 0)
                ->where(function ($query) use ($complementaryColors) {
                    foreach ($complementaryColors as $color) {
                        $query->orWhere('color', 'like', '%' . $color . '%');
                    }
                })
                ->select('id', 'name', 'image_url', 'price_b2c', 'color')
                ->limit(5)
                ->get()
                ->toArray();

            foreach ($products as $product) {
                $recommendations[] = [
                    'product' => $product,
                    'source' => 'hair',
                    'reason' => "Complements your {$hairType} hair",
                    'relevance_score' => 0.75,
                    'service_date' => $service['appointment_date'],
                ];
            }
        }

        return $recommendations;
    }

    private function getMakeupBasedRecommendations(array $service, int $tenantId): array
    {
        $color = $this->extractColor($service['service_name']);
        
        if (!$color) {
            return [];
        }

        $products = DB::table('fashion_products')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where('available_stock', '>', 0)
            ->whereIn('category_id', [1, 2, 3])
            ->where(function ($query) use ($color) {
                $query->where('color', 'like', '%' . $color . '%')
                      ->orWhere('description', 'like', '%' . $color . '%');
            })
            ->select('id', 'name', 'image_url', 'price_b2c', 'color', 'category_id')
            ->limit(5)
            ->get()
            ->toArray();

        return array_map(function ($product) use ($service, $color) {
            return [
                'product' => $product,
                'source' => 'makeup',
                'reason' => "Matches your {$color} makeup",
                'relevance_score' => 0.82,
                'service_date' => $service['appointment_date'],
            ];
        }, $products);
    }

    private function extractColor(string $serviceName): ?string
    {
        $colors = ['red', 'pink', 'nude', 'french', 'dark', 'berry', 'blue', 'green', 'purple', 'black', 'white', 'beige', 'gold', 'silver'];
        
        foreach ($colors as $color) {
            if (stripos($serviceName, $color) !== false) {
                return $color;
            }
        }

        return null;
    }

    private function extractHairType(string $serviceName): ?string
    {
        $hairTypes = ['blonde', 'brunette', 'redhead', 'black', 'brown', 'gray'];
        
        foreach ($hairTypes as $type) {
            if (stripos($serviceName, $type) !== false) {
                return $type;
            }
        }

        return null;
    }

    private function getComplementaryColorsForHair(string $hairType): array
    {
        $colorMap = [
            'blonde' => ['blue', 'green', 'purple', 'pastel'],
            'brunette' => ['red', 'orange', 'gold', 'beige'],
            'redhead' => ['green', 'neutral', 'beige', 'brown'],
            'black' => ['white', 'red', 'blue', 'silver'],
            'brown' => ['blue', 'green', 'cream', 'gold'],
            'gray' => ['blue', 'purple', 'red', 'silver'],
        ];

        return $colorMap[$hairType] ?? ['black', 'white', 'beige'];
    }

    private function sortAndLimit(array $recommendations, int $limit): array
    {
        usort($recommendations, function ($a, $b) {
            return $b['relevance_score'] <=> $a['relevance_score'];
        });

        $seen = [];
        $unique = [];
        foreach ($recommendations as $rec) {
            $key = $rec['product']['id'];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $rec;
            }
        }

        return array_slice($unique, 0, $limit);
    }

    public function getWardrobeUpdateSuggestions(int $userId, int $tenantId): array
    {
        $cacheKey = "fashion_wardrobe_update:{$tenantId}:{$userId}";

        return Cache::remember($cacheKey, Carbon::now()->addSeconds(self::CACHE_TTL), function () use ($userId, $tenantId) {
            $photoSession = DB::table('beauty_appointments')
                ->join('beauty_services', 'beauty_appointments.service_id', '=', 'beauty_services.id')
                ->where('beauty_appointments.user_id', $userId)
                ->where('beauty_appointments.tenant_id', $tenantId)
                ->where('beauty_services.category', 'photo_session')
                ->where('beauty_appointments.status', 'confirmed')
                ->where('beauty_appointments.appointment_date', '>', Carbon::now())
                ->orderBy('beauty_appointments.appointment_date')
                ->first();

            if (!$photoSession) {
                return [];
            }

            $suggestions = [];
            $wardrobeGaps = $this->analyzeWardrobeGaps($userId, $tenantId);
            $trendingItems = $this->getTrendingItems($tenantId);

            foreach ($wardrobeGaps as $gap) {
                $gapItems = $this->findItemsForGap($gap, $trendingItems, $tenantId);
                if (!empty($gapItems)) {
                    $suggestions[] = [
                        'gap_type' => $gap,
                        'reason' => "Complete your wardrobe for the upcoming photo session",
                        'recommended_items' => array_slice($gapItems, 0, 3),
                        'session_date' => $photoSession->appointment_date,
                    ];
                }
            }

            Log::info('Wardrobe update suggestions generated', [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'suggestions_count' => count($suggestions),
            ]);

            return $suggestions;
        });
    }

    private function analyzeWardrobeGaps(int $userId, int $tenantId): array
    {
        $userCategories = DB::table('fashion_order_items')
            ->join('fashion_orders', 'fashion_order_items.fashion_order_id', '=', 'fashion_orders.id')
            ->join('fashion_products', 'fashion_order_items.fashion_product_id', '=', 'fashion_products.id')
            ->where('fashion_orders.user_id', $userId)
            ->where('fashion_orders.tenant_id', $tenantId)
            ->where('fashion_orders.status', 'completed')
            ->distinct()
            ->pluck('fashion_products.category_id')
            ->toArray();

        $allCategories = [1, 2, 3, 4, 5, 6, 7, 8];
        $gaps = array_diff($allCategories, $userCategories);

        $categoryNames = [
            1 => 'tops',
            2 => 'bottoms',
            3 => 'dresses',
            4 => 'outerwear',
            5 => 'activewear',
            6 => 'shoes',
            7 => 'bags',
            8 => 'accessories',
        ];

        return array_map(fn($id) => $categoryNames[$id] ?? "category_{$id}", $gaps);
    }

    private function findItemsForGap(string $gap, array $trendingItems, int $tenantId): array
    {
        $categoryMap = [
            'tops' => 1,
            'bottoms' => 2,
            'dresses' => 3,
            'outerwear' => 4,
            'activewear' => 5,
            'shoes' => 6,
            'bags' => 7,
            'accessories' => 8,
        ];

        $categoryId = $categoryMap[$gap] ?? null;
        if (!$categoryId) {
            return [];
        }

        return DB::table('fashion_products')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where('available_stock', '>', 0)
            ->where('category_id', $categoryId)
            ->select('id', 'name', 'image_url', 'price_b2c', 'color')
            ->limit(5)
            ->get()
            ->toArray();
    }

    private function getTrendingItems(int $tenantId): array
    {
        return DB::table('fashion_products')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where('available_stock', '>', 0)
            ->orderByDesc('trend_score')
            ->limit(20)
            ->select('id', 'name', 'image_url', 'price_b2c', 'color')
            ->get()
            ->toArray();
    }
}
