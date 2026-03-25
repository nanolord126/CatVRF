<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Dynamic Price Suggestion ML Service
 * Рекомендации по динамическому ценообразованию на основе спроса и конкуренции
 * 
 * @package App\Services\Analytics
 * @category ML / Pricing
 */
final class PriceSuggestionMLService
{
    private const CACHE_TTL = 3600; // 1 час
    private const MIN_PRICE_THRESHOLD = 0.8; // Не ниже 80% от себестоимости
    private const MAX_PRICE_THRESHOLD = 2.0; // Не выше 200% от базовой цены

    /**
     * Получает рекомендацию по цене для товара
     * На основе спроса, конкуренции, сезонности и ROI
     * 
     * @param int $productId
     * @param int $tenantId
     * @return array {suggested_price, lower_bound, upper_bound, confidence, reason}
     */
    public function getSuggestedPrice(int $productId, int $tenantId): array
    {
        $cacheKey = "price_suggestion:{$productId}";

        return $this->cache->remember($cacheKey, self::CACHE_TTL, function () use ($productId, $tenantId) {
            try {
                // Получаем исходные данные
                $product = $this->db->table('products')->find($productId);
                if (!$product) {
                    return $this->getDefaultPriceResponse();
                }

                $basePrice = $product->price ?? 1000;
                $cost = $product->cost ?? $basePrice * 0.5;

                // Анализируем спрос
                $demandFactor = $this->analyzeDemand($productId);

                // Анализируем конкуренцию
                $competitionFactor = $this->analyzeCompetition($productId, $basePrice);

                // Анализируем сезонность
                $seasonalFactor = $this->analyzeSeasonality($productId);

                // Вычисляем рекомендуемую цену
                $suggestedPrice = $this->calculateSuggestedPrice(
                    $basePrice,
                    $cost,
                    $demandFactor,
                    $competitionFactor,
                    $seasonalFactor
                );

                // Вычисляем границы
                $lowerBound = max($cost * self::MIN_PRICE_THRESHOLD, $basePrice * 0.7);
                $upperBound = min($basePrice * self::MAX_PRICE_THRESHOLD, $basePrice * 1.5);

                // Обрезаем в границы
                $finalPrice = max($lowerBound, min($upperBound, $suggestedPrice));

                $confidence = $this->calculatePriceConfidence($demandFactor, $competitionFactor);

                return [
                    'suggested_price' => (int)round($finalPrice),
                    'lower_bound' => (int)round($lowerBound),
                    'upper_bound' => (int)round($upperBound),
                    'current_price' => $basePrice,
                    'confidence' => $confidence,
                    'reason' => $this->getPriceChangeReason($finalPrice, $basePrice, $demandFactor),
                    'demand_factor' => round($demandFactor, 2),
                    'competition_factor' => round($competitionFactor, 2),
                    'seasonal_factor' => round($seasonalFactor, 2),
                ];

            } catch (\Throwable $e) {
                $this->log->channel('analytics_errors')->error('Price suggestion failed', [
                    'product_id' => $productId,
                    'error' => $e->getMessage()
                ]);
                return $this->getDefaultPriceResponse();
            }
        });
    }

    /**
     * Анализирует спрос на основе просмотров, добавлений в корзину, продаж
     * Возвращает фактор 0.5 - 1.5 (1.0 = среднее)
     * 
     * @param int $productId
     * @return float
     */
    private function analyzeDemand(int $productId): float
    {
        $last30Days = now()->subDays(30)->startOfDay();

        $views = $this->db->table('user_views')
            ->where('product_id', $productId)
            ->where('created_at', '>=', $last30Days)
            ->count();

        $cartAdds = $this->db->table('cart_items')
            ->where('product_id', $productId)
            ->where('created_at', '>=', $last30Days)
            ->count();

        $sales = $this->db->table('order_items')
            ->where('product_id', $productId)
            ->where('created_at', '>=', $last30Days)
            ->count();

        // Коэффициент конверсии в продажу
        $conversionRate = $views > 0 ? $sales / $views : 0;

        // Если высокий спрос = можно повысить цену
        // Если низкий спрос = нужно снизить цену
        if ($conversionRate > 0.1) {
            // Очень высокий спрос
            return 1.4;
        } elseif ($conversionRate > 0.05) {
            // Хороший спрос
            return 1.2;
        } elseif ($conversionRate < 0.01 && $views > 100) {
            // Много просмотров, но мало продаж
            return 0.7;
        } elseif ($views < 10) {
            // Очень низкий спрос
            return 0.6;
        }

        return 1.0;
    }

    /**
     * Анализирует конкуренцию (средняя цена конкурентов)
     * Возвращает фактор 0.7 - 1.3
     * 
     * @param int $productId
     * @param float $basePrice
     * @return float
     */
    private function analyzeCompetition(int $productId, float $basePrice): float
    {
        $product = $this->db->table('products')->find($productId);
        
        // Получаем среднюю цену конкурентов в той же категории
        $competitorAvgPrice = $this->db->table('products')
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $productId)
            ->where('status', 'active')
            ->avg('price') ?? $basePrice;

        $priceDiff = ($basePrice - $competitorAvgPrice) / $competitorAvgPrice;

        if ($priceDiff < -0.2) {
            // Мы дешевле на 20%+
            return 1.15; // Можем поднять цену
        } elseif ($priceDiff > 0.3) {
            // Мы дороже на 30%+
            return 0.85; // Нужно снизить цену
        }

        return 1.0;
    }

    /**
     * Анализирует сезонные тренды
     * Возвращает фактор 0.8 - 1.4
     * 
     * @param int $productId
     * @return float
     */
    private function analyzeSeasonality(int $productId): float
    {
        $currentMonth = (int)now()->format('m');
        $currentDayOfWeek = (int)now()->format('w');

        // Получаем среднюю продажу в этот месяц за последние 2 года
        $historicalMonthAvg = $this->db->table('order_items')
            ->where('product_id', $productId)
            ->whereRaw('MONTH(created_at) = ?', [$currentMonth])
            ->where('created_at', '>=', now()->subYears(2))
            ->count() / 2; // Делим на 2 года

        // Получаем среднюю продажу во все месяцы
        $overallAvg = $this->db->table('order_items')
            ->where('product_id', $productId)
            ->where('created_at', '>=', now()->subYears(2))
            ->count() / 24; // Делим на 24 месяца

        if ($overallAvg === 0) {
            return 1.0;
        }

        $seasonalRatio = $historicalMonthAvg / $overallAvg;

        // Преобразуем в ценовой фактор
        // Высокий спрос в сезон = повышаем цену
        return min(1.4, max(0.8, 0.5 + $seasonalRatio));
    }

    /**
     * Вычисляет финальную рекомендуемую цену
     * 
     * @param float $basePrice
     * @param float $cost
     * @param float $demandFactor
     * @param float $competitionFactor
     * @param float $seasonalFactor
     * @return float
     */
    private function calculateSuggestedPrice(
        float $basePrice,
        float $cost,
        float $demandFactor,
        float $competitionFactor,
        float $seasonalFactor
    ): float {
        // Взвешиваем факторы: спрос (40%), конкуренция (35%), сезонность (25%)
        $combinedFactor = ($demandFactor * 0.4) + ($competitionFactor * 0.35) + ($seasonalFactor * 0.25);

        // Применяем фактор к базовой цене
        $suggestedPrice = $basePrice * $combinedFactor;

        return $suggestedPrice;
    }

    /**
     * Вычисляет доверие к рекомендации (0-1)
     * 
     * @param float $demandFactor
     * @param float $competitionFactor
     * @return float
     */
    private function calculatePriceConfidence(float $demandFactor, float $competitionFactor): float
    {
        // Если спрос и конкуренция близки к среднему = выше доверие
        $demandConfidence = 1.0 - abs($demandFactor - 1.0) * 0.3;
        $competitionConfidence = 1.0 - abs($competitionFactor - 1.0) * 0.3;

        $combinedConfidence = ($demandConfidence * 0.6) + ($competitionConfidence * 0.4);

        return round(max(0.3, min(0.95, $combinedConfidence)), 2);
    }

    /**
     * Генерирует текстовое объяснение изменения цены
     * 
     * @param float $suggestedPrice
     * @param float $basePrice
     * @param float $demandFactor
     * @return string
     */
    private function getPriceChangeReason(float $suggestedPrice, float $basePrice, float $demandFactor): string
    {
        $priceDiff = (($suggestedPrice - $basePrice) / $basePrice) * 100;

        if ($priceDiff > 10) {
            return 'High demand - consider raising price';
        } elseif ($priceDiff < -10) {
            return 'Low demand - consider reducing price';
        }

        return 'Current price is optimal';
    }

    /**
     * Возвращает дефолтный ответ (когда анализ невозможен)
     * 
     * @return array
     */
    private function getDefaultPriceResponse(): array
    {
        return [
            'suggested_price' => 1000,
            'lower_bound' => 800,
            'upper_bound' => 1500,
            'current_price' => 1000,
            'confidence' => 0.3,
            'reason' => 'Insufficient data for analysis',
            'demand_factor' => 1.0,
            'competition_factor' => 1.0,
            'seasonal_factor' => 1.0,
        ];
    }
}
