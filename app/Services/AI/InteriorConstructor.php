<?php declare(strict_types=1);

namespace App\Services\AI;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class InteriorConstructor extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private RecommendationService $recommendation,
        ) {}

        /**
         * Построить рекомендации интерьера
         */
        public function construct(
            array $analysis,
            array $explicit,
            array $implicit,
            array $params,
        ): array {
            try {
                $styles = $analysis['styles'] ?? [];
                $colors = $analysis['colors'] ?? [];

                // 1. Определить основной стиль
                $mainStyle = $this->determineStyle($styles, $explicit);

                // 2. Выбрать цветовую палитру
                $colorPalette = $this->buildColorPalette($colors, $explicit);

                // 3. Получить рекомендации мебели
                $furniture = $this->recommendFurniture($mainStyle, $colorPalette);

                // 4. Получить рекомендации декора
                $decor = $this->recommendDecor($mainStyle, $colorPalette);

                // 5. Получить рекомендации освещения
                $lighting = $this->recommendLighting($mainStyle);

                // 6. Собрать все товары
                $allItems = \array_merge($furniture, $decor, $lighting);

                // 7. Рассчитать общую цену
                $totalPrice = \array_sum(\array_column($allItems, 'price'));

                Log::channel('audit')->info('Interior construction completed', [
                    'style' => $mainStyle,
                    'items_count' => \count($allItems),
                    'total_price' => $totalPrice,
                ]);

                return [
                    'data' => [
                        'style' => $mainStyle,
                        'color_palette' => $colorPalette,
                        'furniture_count' => \count($furniture),
                        'decor_count' => \count($decor),
                        'lighting_count' => \count($lighting),
                        'total_items' => \count($allItems),
                        'estimated_total' => $totalPrice,
                        'analysis_summary' => [
                            'description' => $analysis['description'],
                            'recommendations' => $analysis['recommendations'] ?? [],
                        ],
                    ],
                    'items' => $allItems,
                    'confidence' => $this->calculateConfidence($analysis, $explicit, $implicit),
                    'confidence_breakdown' => [
                        'style_detection' => $analysis['confidence'] ?? 0.5,
                        'color_matching' => 0.85,
                        'furniture_relevance' => 0.8,
                    ],
                ];
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Interior construction failed', [
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }

        /**
         * Определить основной стиль интерьера
         */
        private function determineStyle(array $detectedStyles, array $explicit): string
        {
            // Явное предпочтение имеет приоритет
            if (!empty($explicit['preferred_styles'])) {
                return $explicit['preferred_styles'][0];
            }

            // Использовать детектированные стили
            if (!empty($detectedStyles)) {
                return $detectedStyles[0];
            }

            return 'modern';
        }

        /**
         * Построить цветовую палитру
         */
        private function buildColorPalette(array $detectedColors, array $explicit): array
        {
            $colors = $detectedColors;

            // Добавить явные предпочтения цветов
            if (!empty($explicit['preferred_colors'])) {
                $colors = \array_merge($colors, $explicit['preferred_colors']);
            }

            // Оставить топ-5 цветов
            return \array_slice(\array_unique($colors), 0, 5);
        }

        /**
         * Получить рекомендации мебели
         */
        private function recommendFurniture(string $style, array $colors): array
        {
            // Получить товары через RecommendationService
            return $this->recommendation->getByAnalysis([
                'category' => 'furniture',
                'style' => $style,
                'colors' => $colors,
                'limit' => 8,
            ]);
        }

        /**
         * Получить рекомендации декора
         */
        private function recommendDecor(string $style, array $colors): array
        {
            return $this->recommendation->getByAnalysis([
                'category' => 'decor',
                'style' => $style,
                'colors' => $colors,
                'limit' => 5,
            ]);
        }

        /**
         * Получить рекомендации освещения
         */
        private function recommendLighting(string $style): array
        {
            return $this->recommendation->getByAnalysis([
                'category' => 'lighting',
                'style' => $style,
                'limit' => 3,
            ]);
        }

        /**
         * Рассчитать уверенность конструктора
         */
        private function calculateConfidence(array $analysis, array $explicit, array $implicit): float
        {
            $analysisConfidence = $analysis['confidence'] ?? 0.5;
            $hasExplicit = !empty($explicit) ? 0.1 : 0;
            $hasImplicit = !empty($implicit) ? 0.05 : 0;

            return \min(1.0, $analysisConfidence + $hasExplicit + $hasImplicit);
        }
}
