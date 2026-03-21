<?php declare(strict_types=1);

namespace App\Services\AI;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class PriceSuggestionService
{
    public function suggestPrice(int $itemId, int $currentPrice, array $context = []): array
    {
        $cacheKey = "price_suggestion:item:{$itemId}";

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Получить среднюю цену конкурентов
        $competitorPrice = $this->getCompetitorAveragePrice($itemId);

        // Получить спрос
        $demand = $this->getDemandLevel($itemId);

        // Рассчитать рекомендацию
        $suggestedPrice = $this->calculateSuggestedPrice($currentPrice, $competitorPrice, $demand);

        $result = [
            'current_price' => $currentPrice,
            'suggested_price' => $suggestedPrice,
            'competitor_price' => $competitorPrice,
            'demand_level' => $demand,
            'confidence' => 0.85,
        ];

        Cache::put($cacheKey, $result, 1800); // 30 минут

        return $result;
    }

    private function getCompetitorAveragePrice(int $itemId): int
    {
        return (int)DB::table('products')
            ->where('category_id', function ($query) use ($itemId) {
                $query->select('category_id')
                    ->from('products')
                    ->where('id', $itemId);
            })
            ->where('id', '!=', $itemId)
            ->avg('price') ?? 0;
    }

    private function getDemandLevel(int $itemId): string
    {
        $ordersPerDay = DB::table('orders')
            ->whereJsonContains('items', ['id' => $itemId])
            ->where('created_at', '>=', now()->subDays(7))
            ->count() / 7;

        if ($ordersPerDay > 10) {
            return 'high';
        }

        if ($ordersPerDay > 5) {
            return 'medium';
        }

        return 'low';
    }

    private function calculateSuggestedPrice(int $current, int $competitor, string $demand): int
    {
        // Если спрос высокий - можно поднять цену
        $multiplier = match ($demand) {
            'high' => 1.1,
            'medium' => 1.0,
            'low' => 0.95,
            default => 1.0,
        };

        // Средняя цена между своей и конкурентов
        $average = (int)(($current + $competitor) / 2);

        return (int)($average * $multiplier);
    }
}
