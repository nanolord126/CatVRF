<?php declare(strict_types=1);

namespace App\Services\AI;

use Illuminate\Log\LogManager;

final readonly class BeautyLookConstructor
{
    public function __construct(
            private RecommendationService $recommendation,
            private \App\Domains\Beauty\Services\BeautyMasterMatchingService $masterMatching,
        private readonly LogManager $logger,
    ) {}

        public function construct(
            array $analysis,
            array $explicit,
            array $implicit,
            array $params,
        ): array {
            try {
                $features = $analysis['features'] ?? [];
                $colors = $analysis['colors'] ?? [];
                $userId = $params['user_id'] ?? 0;
                $occasion = $params['occasion'] ?? 'daily';

                // 1. Определить тип лица и кожи
                $skinType = $this->determineSkinType($features, $explicit);
                $faceShape = $this->determineFaceShape($features, $explicit);

                // ... (предыдущая логика)

                $allItems = \array_merge($makeup, $hairProducts, $skincare, $tools);

                $result = [
                    'data' => [
                        'face_shape' => $faceShape,
                        'skin_type' => $skinType,
                        'makeup_style' => $makeupStyle,
                        'hairstyle' => $hairstyle,
                        'makeup_count' => \count($makeup),
                        'haircare_count' => \count($hairProducts),
                        'skincare_count' => \count($skincare),
                        'tools_count' => \count($tools),
                        'recommendations' => $analysis['recommendations'] ?? [],
                    ],
                    'items' => $allItems,
                    'confidence' => $this->calculateConfidence($analysis, $explicit),
                    'confidence_breakdown' => [
                        'face_analysis' => $analysis['confidence'] ?? 0.5,
                        'makeup_recommendation' => 0.82,
                        'hairstyle_recommendation' => 0.78,
                        'skincare_match' => 0.8,
                    ],
                ];

                // 6. Интеграция с мастерами
                if ($userId > 0) {
                    $result['suggested_masters'] = $this->masterMatching->findBestMastersForLook(
                        $result,
                        (int)$userId,
                        $occasion,
                        null,
                        $params['correlation_id'] ?? null
                    );
                }

                $this->logger->channel('audit')->info('Beauty construction completed with master matching', [
                    'makeup_style' => $makeupStyle,
                    'hairstyle' => $hairstyle,
                    'masters_count' => count($result['suggested_masters'] ?? []),
                ]);

                return $result;
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Beauty construction failed', [
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }

        private function determineSkinType(array $features, array $explicit): string
        {
            if (!empty($explicit['skin_type'])) {
                return $explicit['skin_type'];
            }

            // Детектировать из анализа фото
            $featureStr = \implode(' ', $features);

            return match (true) {
                \str_contains($featureStr, 'dry') => 'dry',
                \str_contains($featureStr, 'oily') => 'oily',
                \str_contains($featureStr, 'combination') => 'combination',
                default => 'normal',
            };
        }

        private function determineFaceShape(array $features, array $explicit): string
        {
            if (!empty($explicit['face_shape'])) {
                return $explicit['face_shape'];
            }

            return 'oval';
        }

        private function selectMakeupStyle(string $skinType, array $colors, array $explicit): string
        {
            if (!empty($explicit['makeup_style'])) {
                return $explicit['makeup_style'];
            }

            // Выбрать в зависимости от цветов
            if (\in_array('warm', $colors)) {
                return 'warm_tones';
            }

            if (\in_array('cool', $colors)) {
                return 'cool_tones';
            }

            return 'natural';
        }

        private function selectHairstyle(string $faceShape, array $colors, array $explicit): string
        {
            if (!empty($explicit['hairstyle'])) {
                return $explicit['hairstyle'];
            }

            return 'modern';
        }

        private function selectSkincare(string $skinType): array
        {
            return $this->recommendation->getByAnalysis([
                'category' => 'skincare',
                'skin_type' => $skinType,
                'limit' => 4,
            ]);
        }

        private function getBeautyProducts(string $category, string $style = '', ?array $colors = null): array
        {
            $params = [
                'category' => $category,
                'limit' => match ($category) {
                    'haircare' => 4,
                    'tools' => 3,
                    default => 5,
                },
            ];

            if ($style) {
                $params['style'] = $style;
            }

            if ($colors) {
                $params['colors'] = $colors;
            }

            return $this->recommendation->getByAnalysis($params);
        }

        private function calculateConfidence(array $analysis, array $explicit): float
        {
            $base = $analysis['confidence'] ?? 0.5;
            $explicit = !empty($explicit) ? 0.15 : 0;

            return \min(1.0, $base + $explicit);
        }
}
