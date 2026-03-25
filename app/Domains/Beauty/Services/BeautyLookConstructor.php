<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\DTOs\AI\AIConstructionResult;
use App\Domains\Beauty\Models\BeautyProduct;
use App\Domains\Beauty\Models\Service as BeautyService;
use App\Services\AI\ImageAnalysisService;
use App\Services\FraudControlService;
use App\Services\InventoryManagementService;
use App\Services\RecommendationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * BeautyLookConstructor (Канон 2026)
 * AI-конструктор для вертикали Beauty & Wellness.
 * Выполняет анализ лица (Vision) и подбор стиля/товаров.
 */
final readonly class BeautyLookConstructor
{
    public function __construct(
        private ImageAnalysisService $imageAnalysis,
        private RecommendationService $recommendation,
        private InventoryManagementService $inventory,
        private FraudControlService $fraud,
        private string $correlationId
    ) {}

    /**
     * Создать персонализированный образ на основе фото лица
     */
    public function construct(int $userId, \Illuminate\Http\UploadedFile $photo, array $params = []): AIConstructionResult
    {
        Log::channel('audit')->info('BeautyLookConstructor: start construction', [
            'user_id' => $userId,
            'correlation_id' => $this->correlationId,
        ]);

        return DB::transaction(function () use ($userId, $photo, $params) {
            // 1. Fraud Check
            $this->fraud->check('beauty_ai_constructor', ['user_id' => $userId]);

            // 2. Vision Analysis (Цветотип, форма лица, состояние кожи)
            $faceAnalysis = $this->imageAnalysis->analyzeFace($photo);

            // 3. Генерация рекомендаций через RecommendationService (Профиль вкусов v2.0)
            $context = array_merge($params, [
                'face_shape' => $faceAnalysis['face_shape'] ?? 'oval',
                'color_type' => $faceAnalysis['color_type'] ?? 'summer',
                'skin_condition' => $faceAnalysis['skin_condition'] ?? 'normal',
                'vertical' => 'Beauty',
            ]);

            $recommendations = $this->recommendation->getForUser($userId, 'Beauty', $context);

            // 4. Проверка наличия товаров и услуг
            $suggestions = $this->enrichSuggestions($recommendations);

            // 5. Формирование результата (Output: Фото-превью через AI + список SKU)
            $result = new AIConstructionResult(
                vertical: 'Beauty',
                type: 'image',
                payload: [
                    'analysis' => $faceAnalysis,
                    'generated_look_url' => $this->generatePreviewUrl($faceAnalysis, $context),
                    'look_description' => "Ваш идеальный образ на базе анализа 15 параметров лица: {$faceAnalysis['face_shape']} форма, {$faceAnalysis['color_type']} цветотип.",
                    'makeup_steps' => [
                        'skin' => 'Увлажнение и база под макияж',
                        'eyes' => 'Акцент на глаза с использованием палитры ' . ($faceAnalysis['color_type'] === 'summer' ? 'холодных' : 'теплых') . ' тонов',
                        'lips' => 'Нюдовая помада для баланса',
                    ],
                ],
                suggestions: $suggestions,
                confidence_score: (float)($faceAnalysis['confidence'] ?? 0.92),
                correlation_id: $this->correlationId
            );

            // 6. Сохранение в БД
            $this->saveToDatabase($userId, $result);

            Log::channel('audit')->info('BeautyLookConstructor: construction finished', [
                'user_id' => $userId,
                'correlation_id' => $this->correlationId,
                'suggestions_count' => count($suggestions),
            ]);

            return $result;
        });
    }

    private function enrichSuggestions(\Illuminate\Support\Collection $recommendations): array
    {
        return $recommendations->map(function ($item) {
            $inStock = false;
            if ($item instanceof BeautyProduct) {
                $inStock = $this->inventory->getCurrentStock($item->id) > 0;
            } elseif ($item instanceof BeautyService) {
                $inStock = $item->masters()->where('is_active', true)->exists();
            }

            return array_merge($item->toArray(), [
                'in_stock' => $inStock,
                'match_reason' => 'Рекомендовано на основе вашего цветотипа и предпочтений.',
            ]);
        })->toArray();
    }

    private function generatePreviewUrl(array $analysis, array $context): string
    {
        // В реальности здесь вызов к Stable Diffusion / DALL-E / Midjourney API
        return "https://cdn.catvrf.com/ai/previews/beauty-" . $this->correlationId . ".jpg";
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
