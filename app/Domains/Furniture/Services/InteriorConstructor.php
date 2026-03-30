<?php declare(strict_types=1);

namespace App\Domains\Furniture\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class InteriorConstructor extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private ImageAnalysisService $imageAnalysis,
            private RecommendationService $recommendation,
            private InventoryManagementService $inventory,
            private FraudControlService $fraud,
            private string $correlationId
        ) {}

        /**
         * Создать проект интерьера на основе фото помещения
         */
        public function construct(int $userId, \Illuminate\Http\UploadedFile $photo, array $params = []): AIConstructionResult
        {
            Log::channel('audit')->info('InteriorConstructor: start construction', [
                'user_id' => $userId,
                'correlation_id' => $this->correlationId,
            ]);

            return DB::transaction(function () use ($userId, $photo, $params) {
                // 1. Fraud Check
                $this->fraud->check('interior_ai_constructor', ['user_id' => $userId]);

                // 2. Vision-мерчандайзинг (анализ пустого или жилого пространства с фото)
                $spaceAnalysis = $this->imageAnalysis->analyzeSpace($photo);

                // 3. Генерация рекомендаций через RecommendationService (Профиль вкусов v2.0)
                $style = $params['style'] ?? 'modern'; // сканди, лофт и т.д.
                $context = array_merge($params, [
                    'room_type' => $spaceAnalysis['room_type'] ?? 'living_room',
                    'space_size_sqm' => $spaceAnalysis['estimated_size_sqm'] ?? 20,
                    'lighting' => $spaceAnalysis['lighting_level'] ?? 'natural',
                    'style' => $style,
                    'vertical' => 'Furniture',
                ]);

                $recommendations = $this->recommendation->getForUser($userId, 'Furniture', $context);

                // 4. Проверка наличия мебели (InventoryManagementService текущего tenant)
                $suggestions = $this->enrichSuggestions($recommendations);

                // 5. Формирование результата (Output: 3D-расстановка мебели + спецификация SKU)
                $result = new AIConstructionResult(
                    vertical: 'Furniture',
                    type: 'design',
                    payload: [
                        'analysis' => $spaceAnalysis,
                        'render_3d_url' => $this->generateRenderUrl($spaceAnalysis, $context),
                        'specification' => [
                            'room_summary' => "AI-дизайнер подобрал " . count($suggestions) . " предметов мебели под ваш метраж ({$context['space_size_sqm']} кв.м.) в стиле {$style}.",
                            'layout_recommendation' => 'Рекомендуется разместить диван вдоль дальней стены для оптимизации освещения.',
                        ],
                    ],
                    suggestions: $suggestions,
                    confidence_score: (float)($spaceAnalysis['confidence'] ?? 0.88),
                    correlation_id: $this->correlationId
                );

                // 6. Сохранение в БД
                $this->saveToDatabase($userId, $result);

                Log::channel('audit')->info('InteriorConstructor: construction finished', [
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
                $inStock = $this->inventory->getCurrentStock($item->id) > 0;

                return array_merge($item->toArray(), [
                    'in_stock' => $inStock,
                    'match_reason' => 'Идеально дополняет выбранный стиль ' . ($inStock ? 'и доступно на складе.' : 'но сейчас не в наличии.'),
                ]);
            })->toArray();
        }

        private function generateRenderUrl(array $analysis, array $context): string
        {
            // В реальности здесь вызов к 3D-рендерер API (например Unreal Cloud / Blender Cloud / V-Ray Cloud)
            return "https://cdn.catvrf.com/ai/renders/interior-" . $this->correlationId . ".glb";
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
