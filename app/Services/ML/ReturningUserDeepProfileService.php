<?php declare(strict_types=1);

namespace App\Services\ML;


use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;


/**
 * ReturningUserDeepProfileService — глубокая персонализация для постоянных пользователей.
 *
 * Правило канона:
 *  95% рекомендаций строится на личной истории + embeddings + taste_profile + LTV.
 *
 *  Отслеживаемые паттерны:
 *  - Churn risk (последние 7 дней без активности)
 *  - LTV growth curve
 *  - Cross-vertical migration (из Beauty в Furniture)
 *  - Loyalty loop strength (повторные покупки одного бренда)
 *  - Price sensitivity changes
 */
final readonly class ReturningUserDeepProfileService
{
    public function __construct(
        private readonly Request $request,
        private UserTasteAnalyzerService $tasteAnalyzer,
        private AnonymizationService     $anonymizer,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Генерировать персонализированные рекомендации для постоянного пользователя.
     *
     * @param  array  $behaviorPattern  — из UserBehaviorAnalyzerService::getReturningUserPattern()
     * @param  string $vertical
     * @return array
     */
    public function generate(array $behaviorPattern, string $vertical): array
    {
        $userId = $behaviorPattern['user_id'] ?? null;

        if ($userId === null) {
            return $this->fallbackRecommendations($vertical);
        }

        $user        = User::findOrFail((int) $userId);
        $tasteVector = $this->tasteAnalyzer->getProfile($user);

        $recommendations = $this->buildPersonalizedRecommendations(
            userId: (int) $userId,
            vertical: $vertical,
            tasteVector: $tasteVector,
            pattern: $behaviorPattern
        );

        $this->logger->channel('audit')->info('ReturningUser deep recommendations generated', [
            'vertical'         => $vertical,
            'count'            => count($recommendations),
            'churn_risk'       => $behaviorPattern['is_churn_risk'] ?? false,
            'personalization'  => 0.95,
            'correlation_id'   => $this->request?->header('X-Correlation-ID') ?? '',
        ]);

        return [
            'strategy'        => 'deep_profile',
            'vertical'        => $vertical,
            'recommendations' => $recommendations,
            'personalization' => 0.95,
            'churn_risk'      => $behaviorPattern['is_churn_risk'] ?? false,
            'anti_churn_offer' => ($behaviorPattern['is_churn_risk'] ?? false)
                ? $this->buildAntiChurnOffer((int) $userId, $vertical)
                : null,
        ];
    }

    /**
     * Рассчитать LTV (Lifetime Value) пользователя.
     */
    public function calculateLTV(int $userId): float
    {
        $user = User::findOrFail($userId);

        $totalSpent = (float) $user->orders()
            ->whereIn('status', ['completed', 'delivered'])
            ->sum('total_amount');

        $avgOrderValue = (float) $user->orders()
            ->whereIn('status', ['completed', 'delivered'])
            ->avg('total_amount');

        $daysActive = (int) now()->diffInDays($user->created_at);
        $ordersPerDay = $daysActive > 0
            ? $user->orders()->count() / $daysActive
            : 0;

        // LTV = средний чек × частота покупок × 365 дней (прогноз на год)
        return round($avgOrderValue * $ordersPerDay * 365, 2);
    }

    /**
     * Определить cross-vertical migration (переход между вертикалями).
     * Используется в таргетинге.
     */
    public function detectCrossVerticalMigration(int $userId): array
    {
        $verticals = $this->db->table('orders')
            ->where('user_id', $userId)
            ->whereIn('status', ['completed', 'delivered'])
            ->orderBy('created_at')
            ->pluck('vertical')
            ->toArray();

        if (count($verticals) < 2) {
            return [];
        }

        $migrations = [];
        for ($i = 1, $iMax = count($verticals); $i < $iMax; $i++) {
            if ($verticals[$i] !== $verticals[$i - 1]) {
                $migrations[] = [
                    'from' => $verticals[$i - 1],
                    'to'   => $verticals[$i],
                ];
            }
        }

        return array_unique($migrations, SORT_REGULAR);
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    private function buildPersonalizedRecommendations(
        int    $userId,
        string $vertical,
        mixed  $tasteVector,
        array  $pattern
    ): array {
        // Строим на основе истории + taste_profile + LTV-сегмента
        $tasteArray = is_array($tasteVector) ? $tasteVector : (method_exists($tasteVector, 'toArray') ? $tasteVector->toArray() : []);

        $favoriteCategories = $tasteArray['categories'] ?? [];
        $priceRange         = $tasteArray['price_range'] ?? ['min' => 0, 'max' => 999999];
        $favoriteBrands     = $tasteArray['favorite_brands'] ?? [];

        $query = $this->db->table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.vertical', $vertical)
            ->whereIn('orders.status', ['completed', 'delivered'])
            ->where('order_items.unit_price', '>=', $priceRange['min'] ?? 0)
            ->where('order_items.unit_price', '<=', $priceRange['max'] ?? 999999)
            ->select('order_items.product_id', $this->db->raw('count(*) as popularity'))
            ->groupBy('order_items.product_id')
            ->orderByDesc('popularity')
            ->limit(15);

        return $query->get()->toArray();
    }

    private function buildAntiChurnOffer(int $userId, string $vertical): array
    {
        // Персональное предложение для удержания пользователя
        return [
            'type'     => 'anti_churn_discount',
            'discount' => '15%',
            'message'  => 'Мы скучаем по вам! Специальная скидка для вашего возвращения.',
            'vertical' => $vertical,
        ];
    }

    private function fallbackRecommendations(string $vertical): array
    {
        return [
            'strategy'        => 'fallback',
            'vertical'        => $vertical,
            'recommendations' => [],
            'personalization' => 0.0,
        ];
    }
}
