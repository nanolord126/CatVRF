<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Services;

use App\Domains\Supermarket\Models\SellerInsight;
use App\Models\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

final readonly class AIInsightGenerator
{
    public function generateForSeller(Tenant $seller): Collection
    {
        $analytics = app(SellerAnalyticsService::class)->getDashboard($seller, '30d');
        $lastMonth = app(SellerAnalyticsService::class)->getDashboard($seller, '30d_ago');

        $insights = $this->generateInsights($seller, $analytics, $lastMonth);

        // Save to database
        foreach ($insights as $insight) {
            SellerInsight::create([
                'tenant_id' => $seller->id,
                ...$insight,
                'generated_at' => now(),
                'expires_at' => now()->addHours(36),
            ]);
        }

        return SellerInsight::where('tenant_id', $seller->id)
            ->where('expires_at', '>', now())
            ->orderBy('impact', 'desc')
            ->get();
    }

    private function generateInsights(Tenant $seller, array $current, array $previous): array
    {
        $insights = [];

        // Revenue growth insight
        $revenueGrowth = $this->calculateGrowth($current['total_revenue'], $previous['total_revenue']);
        if ($revenueGrowth > 20) {
            $insights[] = [
                'type' => 'revenue_growth',
                'title' => 'Выручка растёт',
                'description' => "Выручка выросла на {$revenueGrowth}% по сравнению с прошлым периодом. Отличный результат!",
                'value' => $revenueGrowth,
                'impact' => 'high',
                'actionable' => false,
            ];
        } elseif ($revenueGrowth < -10) {
            $insights[] = [
                'type' => 'revenue_decline',
                'title' => 'Падение выручки',
                'description' => "Выручка снизилась на " . abs($revenueGrowth) . "%. Рекомендую пересмотреть цены или маркетинг.",
                'value' => $revenueGrowth,
                'impact' => 'high',
                'actionable' => true,
            ];
        }

        // Top product insight
        if (!empty($current['top_products']) && isset($current['top_products'][0])) {
            $topProduct = $current['top_products'][0];
            $insights[] = [
                'type' => 'top_product',
                'title' => 'Топ-товар',
                'description' => "Ваш лучший товар (#{$topProduct['product_id']}) приносит {$topProduct['revenue']} ₽.",
                'value' => $topProduct['revenue'],
                'impact' => 'medium',
                'actionable' => true,
            ];
        }

        // Returns insight
        if ($current['returns_rate'] > 8) {
            $insights[] = [
                'type' => 'high_returns',
                'title' => 'Высокий процент возвратов',
                'description' => "Возвраты составляют {$current['returns_rate']}%. Проверьте качество товаров и условия доставки.",
                'value' => $current['returns_rate'],
                'impact' => 'high',
                'actionable' => true,
            ];
        }

        // B2B opportunity
        if ($current['b2b_vs_b2c']['b2b_share'] > 30) {
            $insights[] = [
                'type' => 'b2b_opportunity',
                'title' => 'B2B потенциал',
                'description' => "B2B продажи составляют {$current['b2b_vs_b2c']['b2b_share']}%. Рассмотрите специальные условия для B2B клиентов.",
                'value' => $current['b2b_vs_b2c']['b2b_share'],
                'impact' => 'medium',
                'actionable' => true,
            ];
        }

        return $insights;
    }

    private function calculateGrowth(float $current, float $previous): float
    {
        if ($previous === 0) {
            return $current > 0 ? 100 : 0;
        }

        return (($current - $previous) / $previous) * 100;
    }
}
