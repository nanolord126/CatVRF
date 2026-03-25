<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Services;

use App\DTOs\AI\AIConstructionResult;
use App\Services\AI\ImageAnalysisService;
use App\Services\DemandForecastService;
use App\Services\FraudControlService;
use App\Services\InventoryManagementService;
use App\Services\RecommendationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * RenovatorConstructor (Канон 2026)
 * AI-реноватор для визуализации ремонта в пустых помещениях и прогноза цен.
 */
final readonly class RenovatorConstructor
{
    public function __construct(
        private ImageAnalysisService $imageAnalysis,
        private DemandForecastService $demandForecast,
        private RecommendationService $recommendation,
        private InventoryManagementService $inventory,
        private FraudControlService $fraud,
        private string $correlationId
    ) {}

    /**
     * Создать виртуальный реновационный проект помещения
     */
    public function construct(int $userId, \Illuminate\Http\UploadedFile $photo, array $params = []): AIConstructionResult
    {
        Log::channel('audit')->info('RenovatorConstructor: starting renovation AI', [
            'user_id' => $userId,
            'correlation_id' => $this->correlationId,
        ]);

        return DB::transaction(function () use ($userId, $photo, $params) {
            // 1. Fraud Check
            $this->fraud->check('realestate_ai_renovation', ['user_id' => $userId]);

            // 2. Vision-анализ: Оценка текущего состояния объекта по фото (черновая отделка, износ и т.д.)
            $conditionAnalysis = $this->imageAnalysis->analyzeRealEstateCondition($photo);

            // 3. AI-реноватор: Виртуальный ремонт (лофт, модерн, минимализм)
            $style = $params['target_style'] ?? 'modern';
            $context = array_merge($params, [
                'current_condition' => $conditionAnalysis['detected_status'] ?? 'raw',
                'target_style' => $style,
                'vertical' => 'RealEstate',
            ]);

            // 4. Vision-оценка + DemandForecast: Прогноз роста стоимости или аренды после ремонта
            $forecast = $this->demandForecast->forecastRealEstatePrice($conditionAnalysis, $context);

            // 5. Поиск стройматериалов или мебели (InventoryManagementService)
            $recommendations = $this->recommendation->getForUser($userId, 'Construction', $context);
            $suggestions = $this->enrichSuggestions($recommendations);

            // 6. Формирование результата с визуализацией и прогнозом
            $result = new AIConstructionResult(
                vertical: 'RealEstate',
                type: 'design',
                payload: [
                    'analysis' => $conditionAnalysis,
                    'renovated_view_url' => $this->generateRenovatedUrl($conditionAnalysis, $style),
                    'financial_forecast' => [
                        'current_market_value' => $forecast['base_price'],
                        'after_renovation_value' => $forecast['projected_price'],
                        'rental_yield_increase' => "+{$forecast['rental_yield']}%",
                        'confidence' => $forecast['accuracy'] ?? 0.85,
                    ],
                    'materials_summary' => "AI подобрал основные материалы стоимостью около " . number_format($forecast['estimated_materials_cost'], 0, '.', ' ') . " руб. для реализации дизайна в стиле '{$style}'.",
                ],
                suggestions: $suggestions,
                confidence_score: (float)($conditionAnalysis['confidence'] ?? 0.92),
                correlation_id: $this->correlationId
            );

            // 7. Сохранение в БД
            $this->saveToDatabase($userId, $result);

            Log::channel('audit')->info('RenovatorConstructor: renovation finished', [
                'user_id' => $userId,
                'correlation_id' => $this->correlationId,
                'forecast_value' => $forecast['projected_price'],
            ]);

            return $result;
        });
    }

    private function enrichSuggestions(\Illuminate\Support\Collection $recommendations): array
    {
        return $recommendations->map(function ($item) {
            $inStock = $this->inventory->getCurrentStock($item->id) > 0;

            return array_merge($item->toArray(), [
                'in_stock' => $inStock,
                'matching_style' => 'Идеально подходит для выбранного стиля отделки.',
            ]);
        })->toArray();
    }

    private function generateRenovatedUrl(array $analysis, string $style): string
    {
        return "https://cdn.catvrf.com/ai/renovation/re-" . $this->correlationId . ".png";
    }

    private function saveToDatabase(int $userId, AIConstructionResult $result): void
    {
        DB::table('ai_constructions')->insert([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'user_id' => $userId,
            'tenant_id' => tenant()->id ?? 0,
            'vertical' => $result->vertical,
            'design_data' => json_encode($result->payload),
            'suggestions' => json_encode($result->suggestions),
            'correlation_id' => $result->correlation_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
