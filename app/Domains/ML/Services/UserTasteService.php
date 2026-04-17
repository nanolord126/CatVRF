<?php declare(strict_types=1);

namespace App\Domains\ML\Services;

use App\Domains\ML\Models\UserTasteProfile;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;
use App\Services\AuditService;
use Illuminate\Support\Str;
use App\Services\FraudControlService;

final readonly class UserTasteService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $audit,
        private readonly FraudControlService $fraud,
    ) {}

    /**
     * Update user taste profile based on interaction
     */
    public function updateProfile(int $userId, int $categoryId, int $price, string $correlationId = ''): void
    {
        $correlationId ??= Str::uuid()->toString();

        $this->db->transaction(function () use ($userId, $categoryId, $price, $correlationId) {
            $profile = UserTasteProfile::firstOrCreate(
                ['user_id' => $userId, 'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : 1],
                [
                    'category_preferences' => [],
                    'price_range' => ['min' => 0, 'max' => PHP_INT_MAX],
                    'brand_affinities' => [],
                    'behavioral_score' => 0.0,
                ]
            );

            $preferences = $profile->category_preferences;
            $preferences[$categoryId] = ($preferences[$categoryId] ?? 0) + 1;

            $priceRange = $profile->price_range;
            $priceRange['min'] = min($priceRange['min'], $price);
            $priceRange['max'] = max($priceRange['max'], $price);

            $profile->update([
                'category_preferences' => $preferences,
                'price_range' => $priceRange,
                'behavioral_score' => min(1.0, $profile->behavioral_score + 0.01),
            ]);

            $this->logger->info('User taste profile updated', [
                'user_id' => $userId,
                'category_id' => $categoryId,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Get recommendations for user
     */
    public function getRecommendations(int $userId, int $limit = 10): array
    {
        $profile = UserTasteProfile::where('user_id', $userId)
            ->where('tenant_id', function_exists('tenant') && tenant() ? tenant()->id : 1)
            ->first();

        if (!$profile) {
            return [];
        }

        // Stub: return top preferred categories
        $preferences = $profile->getPreferredCategories();
        arsort($preferences);

        return array_slice(array_keys($preferences), 0, $limit, true);
    }

    /**
     * Cold start recommendations for new users
     */
    public function getColdStartRecommendations(int $userId, string $correlationId = ''): array
    {
        $correlationId ??= Str::uuid()->toString();

        $tenantId = function_exists('tenant') && tenant() ? tenant()->id : 1;

        // Get popular products across tenant
        $popularProducts = $this->db->table('products')
            ->select('id', 'category_id', 'price_kopecks', 'rating', 'sales_count')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->orderBy('sales_count', 'desc')
            ->orderBy('rating', 'desc')
            ->limit(20)
            ->get();

        // Get trending categories (last 7 days)
        $trendingCategories = $this->db->table('order_items')
            ->select('category_id', $this->db->raw('COUNT(*) as order_count'))
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.tenant_id', $tenantId)
            ->where('orders.created_at', '>=', $this->carbon->now()->subDays(7))
            ->groupBy('category_id')
            ->orderBy('order_count', 'desc')
            ->limit(5)
            ->pluck('category_id')
            ->toArray();

        // Prioritize products from trending categories
        $recommendations = [];
        foreach ($popularProducts as $product) {
            if (in_array($product->category_id, $trendingCategories)) {
                array_unshift($recommendations, $product->id);
            } else {
                $recommendations[] = $product->id;
            }
        }

        $this->logger->info('Cold start recommendations generated', [
            'user_id' => $userId,
            'recommendation_count' => count($recommendations),
            'trending_categories' => $trendingCategories,
            'correlation_id' => $correlationId,
        ]);

        return array_unique(array_slice($recommendations, 0, 10));
    }
}
