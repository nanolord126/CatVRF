declare(strict_types=1);

namespace App\Services\AI;

use App\Services\RecommendationService;
use Illuminate\Support\Facades\Log;

/**
 * Cake Constructor
 * Конструктор дизайна тортов
 */
final readonly class CakeConstructor
{
    public function __construct(
        private RecommendationService $recommendation,
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

            // 1. Определить повод
            $occasion = $explicit['occasion'] ?? $params['occasion'] ?? 'birthday';

            // 2. Выбрать размер и форму
            $size = $this->determineSize($params);
            $shape = $explicit['shape'] ?? 'round';

            // 3. Выбрать вкус и начинку
            $flavor = $this->selectFlavor($explicit, $implicit);

            // 4. Выбрать дизайн украшения
            $design = $this->selectDesign($colors, $styles, $occasion);

            // 5. Получить товары (готовые торты + кастомизация)
            $baseCakes = $this->getBaseCakes($flavor, $size, $shape);
            $decorations = $this->getDecorations($design, $colors);
            $additionalServices = $this->getAdditionalServices($occasion);

            $allItems = \array_merge($baseCakes, $decorations, $additionalServices);

            Log::channel('audit')->info('Cake construction completed', [
                'occasion' => $occasion,
                'flavor' => $flavor,
                'design' => $design,
                'items_count' => \count($allItems),
            ]);

            return [
                'data' => [
                    'occasion' => $occasion,
                    'size' => $size,
                    'shape' => $shape,
                    'flavor' => $flavor,
                    'design' => $design,
                    'colors' => \array_slice($colors, 0, 3),
                    'base_cakes_count' => \count($baseCakes),
                    'decorations_count' => \count($decorations),
                    'services_count' => \count($additionalServices),
                    'customization_options' => $this->getCustomizationOptions($occasion),
                ],
                'items' => $allItems,
                'confidence' => $this->calculateConfidence($analysis, $explicit),
                'confidence_breakdown' => [
                    'occasion_matching' => 0.9,
                    'design_relevance' => $analysis['confidence'] ?? 0.5,
                    'flavor_preference' => !empty($explicit['flavor']) ? 0.95 : 0.7,
                ],
            ];
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Cake construction failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function determineSize(array $params): string
    {
        $servings = $params['servings'] ?? 10;

        return match (true) {
            $servings <= 5 => 'small',
            $servings <= 15 => 'medium',
            $servings <= 30 => 'large',
            default => 'extra_large',
        };
    }

    private function selectFlavor(array $explicit, array $implicit): string
    {
        if (!empty($explicit['flavor'])) {
            return $explicit['flavor'];
        }

        // Выбрать на основе неявных предпочтений
        $flavors = $implicit['food_preferences'] ?? [];

        return $flavors[0] ?? 'vanilla';
    }

    private function selectDesign(array $colors, array $styles, string $occasion): string
    {
        $designs = match ($occasion) {
            'birthday' => 'festive',
            'wedding' => 'elegant',
            'corporate' => 'professional',
            'kids' => 'playful',
            'anniversary' => 'romantic',
            default => 'modern',
        };

        return $designs;
    }

    private function getBaseCakes(string $flavor, string $size, string $shape): array
    {
        return $this->recommendation->getByAnalysis([
            'category' => 'cakes',
            'flavor' => $flavor,
            'size' => $size,
            'shape' => $shape,
            'limit' => 3,
        ]);
    }

    private function getDecorations(string $design, array $colors): array
    {
        return $this->recommendation->getByAnalysis([
            'category' => 'cake_decorations',
            'design' => $design,
            'colors' => $colors,
            'limit' => 4,
        ]);
    }

    private function getAdditionalServices(string $occasion): array
    {
        return $this->recommendation->getByAnalysis([
            'category' => 'cake_services',
            'occasion' => $occasion,
            'limit' => 2,
        ]);
    }

    private function getCustomizationOptions(string $occasion): array
    {
        return [
            'custom_message' => true,
            'name_engraving' => true,
            'photo_printing' => $occasion !== 'corporate',
            'dietary_options' => ['vegan', 'gluten_free', 'dairy_free'],
            'delivery' => true,
            'assembly' => true,
        ];
    }

    private function calculateConfidence(array $analysis, array $explicit): float
    {
        $base = $analysis['confidence'] ?? 0.5;
        $explicit = !empty($explicit) ? 0.2 : 0;

        return \min(1.0, $base + $explicit);
    }
}
