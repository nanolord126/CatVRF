<?php

declare(strict_types=1);

namespace App\Domains\WeddingPlanning\Services;

use App\Domains\WeddingPlanning\Models\WeddingVendor;
use App\Domains\WeddingPlanning\Models\WeddingPackage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * AIWeddingPlannerConstructor
 *
 * Layer 3: AI & ML (Constructor & Analytics)
 * Генерирует план свадьбы на основе бюджета, стиля и кол-ва гостей.
 *
 * @version 1.0.0
 * @author CatVRF
 */
final readonly class AIWeddingPlannerConstructor
{
    /**
     * Конструктор
     */
    public function __construct(
        private string $correlationId = ''
    ) {
        $this->correlationId = $this->correlationId ?: (string) Str::uuid();
    }

    /**
     * Генерация плана свадьбы
     *
     * @param int $budget Общий бюджет в копейках
     * @param string $style Стиль (boho, luxury, classic, rustic)
     * @param int $guestCount Количество гостей
     * @return array
     */
    public function generateWeddingPlan(int $budget, string $style, int $guestCount): array
    {
        Log::channel('audit')->info('AIConstructor: Generating wedding plan', [
            'budget' => $budget,
            'style' => $style,
            'guests' => $guestCount,
            'correlation_id' => $this->correlationId
        ]);

        // 1. Распределение бюджета по категориям (AI-логика)
        $distribution = $this->calculateBudgetDistribution($budget, $style);

        // 2. Подбор подрядчиков из базы (Match-making)
        $suggestedVendors = $this->matchVendorsByBudgetAndStyle($distribution, $style);

        // 3. Формирование таймлайна события
        $timeline = $this->generateEventTimeline($style);

        // 4. Расчёт рисков (AI Awareness)
        $risks = $this->assessWeddingRisks($style, $guestCount);

        return [
            'total_budget' => $budget,
            'style' => $style,
            'guest_count' => $guestCount,
            'distribution' => $distribution,
            'suggested_vendors' => $suggestedVendors,
            'timeline' => $timeline,
            'risks' => $risks,
            'correlation_id' => $this->correlationId,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * AI-алгоритм распределения бюджета
     */
    private function calculateBudgetDistribution(int $budget, string $style): array
    {
        $ratios = match ($style) {
            'luxury' => ['venue' => 0.40, 'catering' => 0.30, 'decor' => 0.15, 'photo' => 0.10, 'other' => 0.05],
            'boho' => ['venue' => 0.20, 'catering' => 0.25, 'decor' => 0.35, 'photo' => 0.15, 'other' => 0.05],
            'rustic' => ['venue' => 0.25, 'catering' => 0.30, 'decor' => 0.25, 'photo' => 0.10, 'other' => 0.10],
            default => ['venue' => 0.30, 'catering' => 0.30, 'decor' => 0.20, 'photo' => 0.15, 'other' => 0.05],
        };

        $distribution = [];
        foreach ($ratios as $category => $ratio) {
            $distribution[$category] = (int) ($budget * $ratio);
        }

        return $distribution;
    }

    /**
     * Поиск вендоров под бюджет категории
     */
    private function matchVendorsByBudgetAndStyle(array $distribution, string $style): Collection
    {
        return WeddingVendor::where('is_verified', true)
            ->whereIn('category', array_keys($distribution))
            ->get()
            ->filter(function ($vendor) use ($distribution) {
                return $vendor->base_price <= ($distribution[$vendor->category] ?? 0);
            })
            ->groupBy('category');
    }

    /**
     * Генерация таймлайна
     */
    private function generateEventTimeline(string $style): array
    {
        return [
            ['time' => '10:00', 'action' => 'Morning prep (Bride & Groom)'],
            ['time' => '14:00', 'action' => 'Official Ceremony'],
            ['time' => '16:00', 'action' => 'Photo session & Cocktails'],
            ['time' => '18:00', 'action' => 'Dinner & Grand Entrance'],
            ['time' => '21:00', 'action' => 'Cake cutting & Dance'],
            ['time' => '23:00', 'action' => 'Fireworks / Farewell'],
        ];
    }

    /**
     * Анализ рисков на базе ML (заглушка с логикой)
     */
    private function assessWeddingRisks(string $style, int $guests): array
    {
        $risks = [];
        if ($guests > 100) $risks[] = 'High logistical risk for transport and catering';
        if ($style === 'boho' || $style === 'rustic') $risks[] = 'Outdoor event: Weather dependency (need Tent fallback)';

        return $risks;
    }
}
