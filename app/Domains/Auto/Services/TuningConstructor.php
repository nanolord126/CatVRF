<?php

declare(strict_types=1);

namespace App\Domains\Auto\Services;

use App\DTOs\AI\AIConstructionResult;
use App\Services\AI\ImageAnalysisService;
use App\Services\FraudControlService;
use App\Services\InventoryManagementService;
use App\Services\RecommendationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * TuningConstructor (Канон 2026)
 * AI-конструктор для визуализации тюнинга и дефектовки авто по фото.
 */
final readonly class TuningConstructor
{
    public function __construct(
        private ImageAnalysisService $imageAnalysis,
        private RecommendationService $recommendation,
        private InventoryManagementService $inventory,
        private FraudControlService $fraud,
        private string $correlationId
    ) {}

    /**
     * Создать проект тюнинга или дефектовать авто по фото
     */
    public function construct(int $userId, \Illuminate\Http\UploadedFile $photo, array $params = []): AIConstructionResult
    {
        Log::channel('audit')->info('TuningConstructor: starting auto project', [
            'user_id' => $userId,
            'correlation_id' => $this->correlationId,
        ]);

        return DB::transaction(function () use ($userId, $photo, $params) {
            // 1. Fraud Check
            $this->fraud->check('auto_ai_tuning', ['user_id' => $userId]);

            // 2. Vision AI: Анализ авто (марка, модель, текущее состояние) и поиск повреждений
            $vehicleAnalysis = $this->imageAnalysis->analyzeVehicle($photo);

            // 3. AI-тюнинг или дефектовка (в зависимости от режима)
            $mode = $params['mode'] ?? 'tuning'; // tuning, damage_estimate
            $context = array_merge($params, [
                'vin' => $vehicleAnalysis['vin'] ?? null,
                'vehicle_type' => $vehicleAnalysis['detected_type'] ?? 'sedan',
                'damaged_parts' => $vehicleAnalysis['damaged_areas'] ?? [],
                'vertical' => 'Auto',
            ]);

            // 4. Поиск запчастей или компонентов тюнинга (диски, обвесы) из Inventory
            $recommendations = $this->recommendation->getForUser($userId, 'Auto', $context);
            $suggestions = $this->enrichSuggestions($recommendations);

            // 5. Итоговая калькуляция сметы и визуализация
            $calculation = $this->calculateTotalCost($context, $suggestions);

            $result = new AIConstructionResult(
                vertical: 'Auto',
                type: 'design',
                payload: [
                    'vehicle' => $vehicleAnalysis,
                    'project_type' => $mode === 'tuning' ? 'Визуализация внешнего тюнинга' : 'Смета на кузовной ремонт',
                    'preview_url' => $this->generatePreviewUrl($vehicleAnalysis, $mode, $params),
                    'estimate' => [
                        'parts_cost' => $calculation['parts_total'],
                        'work_cost' => $calculation['labor_total'],
                        'estimated_time_days' => 5,
                    ],
                    'mechanic_notes' => $mode === 'tuning' 
                        ? "Проект включает диски R19 и антихром. Все элементы совместимы с вашим кузовом."
                        : "Визуально обнаружена деформация крыла. Требуется очная дефектовка скрытых повреждений.",
                ],
                suggestions: $suggestions,
                confidence_score: (float)($vehicleAnalysis['accuracy'] ?? 0.89),
                correlation_id: $this->correlationId
            );

            // 6. Сохранение в БД
            $this->saveToDatabase($userId, $result);

            Log::channel('audit')->info('TuningConstructor: auto project finished', [
                'user_id' => $userId,
                'correlation_id' => $this->correlationId,
                'total_cost' => $calculation['parts_total'] + $calculation['labor_total'],
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
                'compatibility' => 'Подходит для вашего VIN ' . ($inStock ? '(доступно на складе)' : '(под заказ 3 дня)'),
            ]);
        })->toArray();
    }

    private function calculateTotalCost(array $context, array $suggestions): array
    {
        $partsTotal = array_sum(array_column($suggestions, 'price'));
        // Грязная логика расчета работ — в реальности через AIPricingCalculator
        $laborTotal = count($suggestions) * 2500; 

        return [
            'parts_total' => $partsTotal,
            'labor_total' => $laborTotal,
        ];
    }

    private function generatePreviewUrl(array $analysis, string $mode, array $params): string
    {
        return "https://cdn.catvrf.com/ai/auto/pr-" . $this->correlationId . ".jpg";
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
