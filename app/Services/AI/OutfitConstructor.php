<?php declare(strict_types=1);

namespace App\Services\AI;


use Illuminate\Http\Request;
use App\Services\RecommendationService;


use Illuminate\Support\Str;
use Illuminate\Log\LogManager;

final readonly class OutfitConstructor
{
    public function __construct(
        private readonly Request $request,
            private RecommendationService $recommendation,
        private readonly LogManager $logger,
    ) {}

        public function construct(
            array $analysis,
            array $explicit,
            array $implicit,
            array $params,
        ): array {
            try {
                $colors = $analysis['colors'] ?? [];
                $styles = $analysis['styles'] ?? [];
                $elements = $analysis['elements'] ?? [];

                // 1. Определить основной стиль
                $outfit_style = $this->determineOutfitStyle($styles, $explicit);

                // 2. Выбрать цветовую схему
                $colorScheme = $this->buildColorScheme($colors, $explicit);

                // 3. Получить товары одежды
                $tops = $this->getClothingItems('tops', $outfit_style, $colorScheme);
                $bottoms = $this->getClothingItems('bottoms', $outfit_style, $colorScheme);
                $outerwear = $this->getClothingItems('outerwear', $outfit_style, $colorScheme);
                $shoes = $this->getClothingItems('shoes', $outfit_style, $colorScheme);

                // 4. Получить аксессуары
                $accessories = $this->getAccessories($outfit_style, $colorScheme);

                $allItems = \array_merge($tops, $bottoms, $outerwear, $shoes, $accessories);

                $this->logger->channel('audit')->info('Outfit construction completed', [
                    'style' => $outfit_style,
                    'colors' => $colorScheme,
                    'items_count' => \count($allItems),
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);

                return [
                    'data' => [
                        'style' => $outfit_style,
                        'color_scheme' => $colorScheme,
                        'tops_count' => \count($tops),
                        'bottoms_count' => \count($bottoms),
                        'outerwear_count' => \count($outerwear),
                        'shoes_count' => \count($shoes),
                        'accessories_count' => \count($accessories),
                        'outfit_combinations' => $this->generateOutfitCombinations(
                            $tops,
                            $bottoms,
                            $outerwear,
                            $shoes,
                        ),
                    ],
                    'items' => $allItems,
                    'confidence' => $this->calculateConfidence($analysis, $explicit),
                    'confidence_breakdown' => [
                        'style_detection' => $analysis['confidence'] ?? 0.5,
                        'color_matching' => 0.85,
                        'clothing_relevance' => 0.82,
                        'size_match' => 0.8,
                    ],
                ];
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Outfit construction failed', [
                    'error' => $e->getMessage(),
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
                throw $e;
            }
        }

        private function determineOutfitStyle(array $styles, array $explicit): string
        {
            if (!empty($explicit['outfit_style'])) {
                return $explicit['outfit_style'];
            }

            return $styles[0] ?? 'casual';
        }

        private function buildColorScheme(array $colors, array $explicit): array
        {
            $scheme = $colors;

            if (!empty($explicit['colors'])) {
                $scheme = \array_merge($scheme, $explicit['colors']);
            }

            return \array_slice(\array_unique($scheme), 0, 3);
        }

        private function getClothingItems(string $category, string $style, array $colors): array
        {
            return $this->recommendation->getByAnalysis([
                'category' => $category,
                'style' => $style,
                'colors' => $colors,
                'limit' => match ($category) {
                    'bottoms' => 3,
                    'outerwear' => 2,
                    'shoes' => 2,
                    default => 2,
                },
            ]);
        }

        private function getAccessories(string $style, array $colors): array
        {
            return $this->recommendation->getByAnalysis([
                'category' => 'accessories',
                'style' => $style,
                'colors' => $colors,
                'limit' => 4,
            ]);
        }

        private function generateOutfitCombinations(array $tops, array $bottoms, array $outerwear, array $shoes): array
        {
            $combinations = [];

            foreach ($tops as $top) {
                foreach ($bottoms as $bottom) {
                    $outfit = [
                        'top' => $top['id'],
                        'bottom' => $bottom['id'],
                        'shoes' => $shoes[0]['id'] ?? null,
                        'outerwear' => $outerwear[0]['id'] ?? null,
                    ];

                    $combinations[] = $outfit;

                    if (\count($combinations) >= 5) {
                        break 2;
                    }
                }
            }

            return $combinations;
        }

        private function calculateConfidence(array $analysis, array $explicit): float
        {
            $base = $analysis['confidence'] ?? 0.5;
            $explicit = !empty($explicit) ? 0.15 : 0;

            return \min(1.0, $base + $explicit);
        }
}
