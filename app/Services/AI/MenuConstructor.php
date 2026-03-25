declare(strict_types=1);

namespace App\Services\AI;

use App\Services\RecommendationService;
use Illuminate\Support\Facades\Log;

/**
 * Menu Constructor
 * Конструктор подбора меню для корпоративного питания
 */
final readonly class MenuConstructor
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
            $elements = $analysis['elements'] ?? [];

            // 1. Определить тип мероприятия
            $eventType = $explicit['event_type'] ?? 'meeting';

            // 2. Определить количество человек
            $guestCount = (int)($params['guest_count'] ?? 10);

            // 3. Определить бюджет
            $budget = (int)($explicit['budget'] ?? 500 * $guestCount);

            // 4. Определить диетические ограничения
            $dietaryRestrictions = $explicit['dietary'] ?? [];

            // 5. Получить рекомендации блюд
            $mainCourses = $this->getMainCourses($eventType, $dietaryRestrictions, $budget, $guestCount);
            $appetizers = $this->getAppetizers($eventType, $dietaryRestrictions);
            $desserts = $this->getDesserts($eventType);
            $beverages = $this->getBeverages($eventType, $guestCount);

            // 6. Рассчитать общую стоимость
            $allItems = \array_merge($mainCourses, $appetizers, $desserts, $beverages);
            $totalPrice = \array_sum(\array_column($allItems, 'price'));

            Log::channel('audit')->info('Menu construction completed', [
                'event_type' => $eventType,
                'guest_count' => $guestCount,
                'budget' => $budget,
                'items_count' => \count($allItems),
                'total_price' => $totalPrice,
            ]);

            return [
                'data' => [
                    'event_type' => $eventType,
                    'guest_count' => $guestCount,
                    'budget' => $budget,
                    'dietary_restrictions' => $dietaryRestrictions,
                    'main_courses_count' => \count($mainCourses),
                    'appetizers_count' => \count($appetizers),
                    'desserts_count' => \count($desserts),
                    'beverages_count' => \count($beverages),
                    'total_items' => \count($allItems),
                    'estimated_total' => $totalPrice,
                    'cost_per_person' => $guestCount > 0 ? (int)($totalPrice / $guestCount) : 0,
                ],
                'items' => $allItems,
                'confidence' => $this->calculateConfidence($analysis, $explicit),
                'confidence_breakdown' => [
                    'event_matching' => 0.88,
                    'budget_alignment' => $this->calculateBudgetAlignment($totalPrice, $budget),
                    'menu_variety' => 0.85,
                    'dietary_compliance' => !empty($dietaryRestrictions) ? 0.9 : 0.95,
                ],
            ];
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Menu construction failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function getMainCourses(
        string $eventType,
        array $dietary,
        int $budget,
        int $guestCount,
    ): array {
        $itemBudget = (int)($budget * 0.4 / $guestCount);

        $params = [
            'category' => 'main_course',
            'event_type' => $eventType,
            'max_price' => $itemBudget,
            'limit' => 3,
        ];

        if (!empty($dietary)) {
            $params['dietary'] = $dietary;
        }

        return $this->recommendation->getByAnalysis($params);
    }

    private function getAppetizers(string $eventType, array $dietary): array
    {
        $params = [
            'category' => 'appetizers',
            'event_type' => $eventType,
            'limit' => 4,
        ];

        if (!empty($dietary)) {
            $params['dietary'] = $dietary;
        }

        return $this->recommendation->getByAnalysis($params);
    }

    private function getDesserts(string $eventType): array
    {
        return $this->recommendation->getByAnalysis([
            'category' => 'desserts',
            'event_type' => $eventType,
            'limit' => 2,
        ]);
    }

    private function getBeverages(string $eventType, int $guestCount): array
    {
        return $this->recommendation->getByAnalysis([
            'category' => 'beverages',
            'event_type' => $eventType,
            'quantity' => $guestCount,
            'limit' => 4,
        ]);
    }

    private function calculateBudgetAlignment(int $actual, int $budget): float
    {
        if ($budget === 0) {
            return 0.5;
        }

        $ratio = (float)$actual / $budget;

        return match (true) {
            $ratio <= 0.9 => 1.0,
            $ratio <= 1.0 => 0.95,
            $ratio <= 1.1 => 0.85,
            default => 0.5,
        };
    }

    private function calculateConfidence(array $analysis, array $explicit): float
    {
        $base = $analysis['confidence'] ?? 0.5;
        $explicit = !empty($explicit) ? 0.2 : 0;

        return \min(1.0, $base + $explicit);
    }
}
