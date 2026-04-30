<?php

declare(strict_types=1);

namespace App\Http\Controllers\Supermarket;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

final readonly class SupermarketRecommendationController
{
    public function ai(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $tenantId = $request->user()?->tenant_id;

        // Get user's taste profile (simplified - would use ML in production)
        $tasteProfile = $this->getUserTasteProfile($userId);

        // Get recommendations based on taste profile
        $products = $this->getPersonalizedProducts($tasteProfile, $tenantId);

        return response()->json([
            'products' => $products,
            'taste_profile' => $tasteProfile,
        ]);
    }

    public function crossSell(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $tenantId = $request->user()?->tenant_id;

        // Get cart items to find cross-sell opportunities
        $cartProducts = $this->getCartProductIds($userId, $tenantId);
        
        if (empty($cartProducts)) {
            return response()->json([]);
        }

        // Find complementary products
        $crossSellProducts = $this->getComplementaryProducts($cartProducts, $tenantId);

        return response()->json($crossSellProducts);
    }

    private function getUserTasteProfile(int $userId): array
    {
        // Simplified - would use ML model in production
        $recentOrders = DB::table('supermarket_orders')
            ->where('user_id', $userId)
            ->where('status', 'delivered')
            ->limit(10)
            ->pluck('sub_vertical')
            ->toArray();

        $profile = [];
        foreach (array_count_values($recentOrders) as $subVertical => $count) {
            if ($count >= 2) {
                $profile[] = $this->getSubVerticalLabel($subVertical);
            }
        }

        return $profile ?: ['Обычные продукты'];
    }

    private function getPersonalizedProducts(array $tasteProfile, ?string $tenantId): array
    {
        $cacheKey = "supermarket:ai_recommendations:{$userId}:{$tenantId}";

        return Cache::tags(['supermarket', 'recommendations'])
            ->remember($cacheKey, now()->addMinutes(15), function () use ($tasteProfile, $tenantId) {
                $subVerticals = array_map(fn($label) => $this->getSubVerticalIdFromLabel($label), $tasteProfile);
                
                return DB::table('products')
                    ->whereIn('sub_vertical', $subVerticals ?: ['grocery_and_delivery'])
                    ->where('is_active', true)
                    ->inRandomOrder()
                    ->limit(4)
                    ->select([
                        'id',
                        'name',
                        'price',
                        'sub_vertical',
                        'requires_cold_chain',
                        'image_url as image',
                    ])
                    ->get()
                    ->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'price' => $product->price,
                            'sub_vertical' => $product->sub_vertical,
                            'requires_cold_chain' => (bool) $product->requires_cold_chain,
                            'image' => $product->image,
                            'match_score' => rand(75, 95),
                            'reason' => 'Основано на ваших предыдущих заказах',
                        ];
                    })
                    ->toArray();
            });
    }

    private function getCartProductIds(int $userId, ?string $tenantId): array
    {
        return DB::table('cart_items')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('reservation_expires_at', '>', now())
            ->pluck('product_id')
            ->toArray();
    }

    private function getComplementaryProducts(array $cartProductIds, ?string $tenantId): array
    {
        // Get sub-verticals of cart items
        $cartSubVerticals = DB::table('products')
            ->whereIn('id', $cartProductIds)
            ->pluck('sub_vertical')
            ->unique()
            ->toArray();

        // Find products from related sub-verticals
        $relatedSubVerticals = $this->getRelatedSubVerticals($cartSubVerticals);

        return DB::table('products')
            ->whereIn('sub_vertical', $relatedSubVerticals)
            ->whereNotIn('id', $cartProductIds)
            ->where('is_active', true)
            ->inRandomOrder()
            ->limit(4)
            ->select([
                'id',
                'name',
                'price',
                'sub_vertical',
                'requires_cold_chain',
                'image_url as image',
            ])
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'sub_vertical' => $product->sub_vertical,
                    'requires_cold_chain' => (bool) $product->requires_cold_chain,
                    'image' => $product->image,
                ];
            })
            ->toArray();
    }

    private function getRelatedSubVerticals(array $currentSubVerticals): array
    {
        $relations = [
            'meat_shops' => ['grocery_and_delivery', 'confectionery'],
            'farm_direct' => ['grocery_and_delivery', 'vegan_products'],
            'vegan_products' => ['farm_direct', 'grocery_and_delivery'],
            'confectionery' => ['grocery_and_delivery', 'meat_shops'],
            'grocery_and_delivery' => ['meat_shops', 'farm_direct', 'confectionery'],
            'food' => ['grocery_and_delivery'],
            'office_catering' => ['food', 'confectionery'],
        ];

        $related = [];
        foreach ($currentSubVerticals as $sv) {
            $related = array_merge($related, $relations[$sv] ?? []);
        }

        return array_unique($related) ?: ['grocery_and_delivery'];
    }

    private function getSubVerticalLabel(string $subVertical): string
    {
        $labels = [
            'meat_shops' => 'Мясные',
            'farm_direct' => 'Фермерские',
            'vegan_products' => 'Веган',
            'confectionery' => 'Кондитерские',
            'grocery_and_delivery' => 'Бакалея',
            'food' => 'Еда',
            'office_catering' => 'Кейтеринг',
        ];
        return $labels[$subVertical] ?? $subVertical;
    }

    private function getSubVerticalIdFromLabel(string $label): string
    {
        $ids = [
            'Мясные' => 'meat_shops',
            'Фермерские' => 'farm_direct',
            'Веган' => 'vegan_products',
            'Кондитерские' => 'confectionery',
            'Бакалея' => 'grocery_and_delivery',
            'Еда' => 'food',
            'Кейтеринг' => 'office_catering',
        ];
        return $ids[$label] ?? 'grocery_and_delivery';
    }
}
