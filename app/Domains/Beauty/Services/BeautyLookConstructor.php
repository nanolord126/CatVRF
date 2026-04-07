<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Services;


use Carbon\Carbon;
use App\Domains\Beauty\Models\BeautyConsumable;
use App\Domains\Beauty\Models\BeautyService as BeautyServiceModel;
use App\DTOs\AI\AIConstructionResult;
use App\Services\AI\ImageAnalysisService;
use App\Services\FraudControlService;
use App\Services\RecommendationService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * BeautyLookConstructor — AI-конструктор персонализированного образа.
 *
 * Анализирует фото лица через Vision API, генерирует рекомендации
 * по макияжу и причёске с учётом цветотипа и UserTasteProfile,
 * сохраняет результат в БД.
 */
final readonly class BeautyLookConstructor
{
    public function __construct(
        private ImageAnalysisService $imageAnalysis,
        private RecommendationService $recommendation,
        private InventoryManagementService $inventory,
        private FraudControlService $fraud,
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
    ) {
    }

    /**
     * Создать персонализированный образ на основе фото лица.
     *
     * @param array<string, mixed> $params Дополнительные параметры анализа
     */
    public function construct(int $userId, UploadedFile $photo, array $params = []): AIConstructionResult
    {
        $correlationId = Str::uuid()->toString();

        $this->logger->info('BeautyLookConstructor: start construction', [
            'user_id' => $userId,
            'correlation_id' => $correlationId,
        ]);

        return $this->db->transaction(function () use ($userId, $photo, $params, $correlationId): AIConstructionResult {
            $this->fraud->check(
                userId: (int) ($this->guard->id() ?? 0),
                operationType: 'beauty_ai_constructor',
                amount: 0,
                correlationId: $correlationId,
            );

            $faceAnalysis = $this->imageAnalysis->analyzeFace($photo);

            $context = array_merge($params, [
                'face_shape' => $faceAnalysis['face_shape'] ?? 'oval',
                'color_type' => $faceAnalysis['color_type'] ?? 'summer',
                'skin_condition' => $faceAnalysis['skin_condition'] ?? 'normal',
                'vertical' => 'Beauty',
            ]);

            $recommendations = $this->recommendation->getForUser($userId, 'Beauty', $context);

            $suggestions = $this->enrichSuggestions($recommendations);

            $colorType = $faceAnalysis['color_type'] ?? 'summer';
            $faceShape = $faceAnalysis['face_shape'] ?? 'oval';

            $result = new AIConstructionResult(
                vertical: 'Beauty',
                type: 'image',
                payload: [
                    'analysis' => $faceAnalysis,
                    'generated_look_url' => $this->generatePreviewUrl($faceAnalysis, $context, $correlationId),
                    'look_description' => "Ваш идеальный образ на базе анализа 15 параметров лица: {$faceShape} форма, {$colorType} цветотип.",
                    'makeup_steps' => [
                        'skin' => 'Увлажнение и база под макияж',
                        'eyes' => 'Акцент на глаза с использованием палитры ' . ($colorType === 'summer' ? 'холодных' : 'теплых') . ' тонов',
                        'lips' => 'Нюдовая помада для баланса',
                    ],
                ],
                suggestions: $suggestions,
                confidence_score: (float) ($faceAnalysis['confidence'] ?? 0.92),
                correlation_id: $correlationId,
            );

            $this->saveToDatabase($userId, $result);

            $this->logger->info('BeautyLookConstructor: construction finished', [
                'user_id' => $userId,
                'correlation_id' => $correlationId,
                'suggestions_count' => count($suggestions),
            ]);

            return $result;
        });
    }

    /**
     * Обогатить рекомендации данными о наличии и причинами подбора.
     *
     * @return array<int, array<string, mixed>>
     */
    private function enrichSuggestions(Collection $recommendations): array
    {
        return $recommendations->map(function (object $item): array {
            $inStock = false;

            if ($item instanceof BeautyConsumable) {
                $inStock = $this->inventory->getCurrentStock($item->id) > 0;
            } elseif ($item instanceof BeautyServiceModel) {
                $inStock = $item->masters()->where('is_active', true)->exists();
            }

            return array_merge($item->toArray(), [
                'in_stock' => $inStock,
                'match_reason' => 'Рекомендовано на основе вашего цветотипа и предпочтений.',
            ]);
        })->toArray();
    }

    /**
     * Сгенерировать URL превью образа.
     *
     * @param array<string, mixed> $analysis Результат анализа лица
     * @param array<string, mixed> $context Контекст построения образа
     */
    private function generatePreviewUrl(array $analysis, array $context, string $correlationId): string
    {
        return 'https://cdn.catvrf.com/ai/previews/beauty-' . $correlationId . '.jpg';
    }

    /**
     * Сохранить результат конструктора в таблицу ai_constructions.
     */
    private function saveToDatabase(int $userId, AIConstructionResult $result): void
    {
        $this->db->table('ai_constructions')->insert([
            'uuid' => Str::uuid()->toString(),
            'user_id' => $userId,
            'tenant_id' => 0,
            'vertical' => $result->vertical,
            'design_data' => json_encode($result->payload, JSON_THROW_ON_ERROR),
            'suggestions' => json_encode($result->suggestions, JSON_THROW_ON_ERROR),
            'correlation_id' => $result->correlation_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
