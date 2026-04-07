<?php declare(strict_types=1);

namespace App\Services\ML;


use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;



/**
 * NewUserColdStartService — персонализация для новых пользователей.
 *
 * Правило канона:
 *  70% рекомендаций строится на похожих пользователях (collaborative filtering),
 *  30% — на device + geo + first actions.
 *
 *  Cold-start отслеживаемые паттерны:
 *  - Speed of first purchase (время до первой покупки)
 *  - Number of verticals explored in first 3 sessions
 *  - AR/AI constructor usage in first 24h
 *  - Bounce rate vs deep engagement
 */
final readonly class NewUserColdStartService
{
    public function __construct(
        private readonly Request $request,
        private UserTasteAnalyzerService $tasteAnalyzer,
        private AnonymizationService     $anonymizer,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Генерировать рекомендации для нового пользователя.
     *
     * @param  array  $behaviorPattern  — паттерн из UserBehaviorAnalyzerService::getNewUserPattern()
     * @param  string $vertical         — вертикаль (beauty, food, furniture и т.д.)
     * @return array                    — обезличенные рекомендации
     */
    public function generate(array $behaviorPattern, string $vertical): array
    {
        // Для нового пользователя нет личной истории —
        // используем popular-items + region-based рекомендации
        $popular = $this->getPopularByVertical($vertical);

        $deviceType = $behaviorPattern['device_type'] ?? 'unknown';

        $this->logger->channel('audit')->info('NewUserColdStart recommendations generated', [
            'vertical'    => $vertical,
            'device_type' => $deviceType,
            'count'       => count($popular),
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);

        return [
            'strategy'       => 'cold_start',
            'vertical'       => $vertical,
            'recommendations' => $popular,
            'personalization' => 0.30, // 30% персонализации у нового пользователя
        ];
    }

    /**
     * Оценить engagement нового пользователя (cold-start score).
     * Высокий score → быстрее переводим в категорию returning.
     */
    public function calculateEngagementScore(int $userId): float
    {
        $user           = \App\Models\User::findOrFail($userId);
        $hoursOld       = now()->diffInHours($user->created_at);
        $hasOrder       = $user->orders()->exists();
        $hasAiUsage     = $this->db->table('user_ai_designs')->where('user_id', $userId)->exists();
        $pagesVisited   = $this->db->table('user_sessions')
            ->where('user_id', $userId)
            ->sum('pages_visited');

        $score = 0.0;

        if ($hasOrder) {
            $score += 0.5;             // первая покупка — большой вес
        }

        if ($hasAiUsage) {
            $score += 0.2;             // использование AI-конструктора
        }

        if ((int) $pagesVisited > 10) {
            $score += 0.15;            // deep engagement
        }

        if ($hoursOld < 24 && $hasOrder) {
            $score += 0.15;            // быстрая первая покупка
        }

        return min(1.0, $score);
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    private function getPopularByVertical(string $vertical): array
    {
        // Топ-10 популярных продуктов в вертикали за последние 7 дней
        return $this->db->table('orders')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.vertical', $vertical)
            ->where('orders.created_at', '>=', now()->subDays(7))
            ->whereIn('orders.status', ['completed', 'delivered'])
            ->select('order_items.product_id', $this->db->raw('count(*) as popularity'))
            ->groupBy('order_items.product_id')
            ->orderByDesc('popularity')
            ->limit(10)
            ->get()
            ->toArray();
    }
}
