<?php declare(strict_types=1);

namespace App\Services\ML;


use Illuminate\Http\Request;
use App\Jobs\MLRecalculateJob;
use App\Models\User;

use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

/**
 * UserBehaviorAnalyzerService — главный ML-оркестратор.
 *
 * Правила канона:
 *  - Разделяет NEW vs RETURNING пользователей по критериям
 *  - processEvent() обезличивает данные ПЕРЕД записью в ClickHouse
 *  - user_id НИКОГДА не попадает в anonymized_behavior
 *  - Онлайн-обучение ML-моделей в 5% событий (→ MLRecalculateJob)
 *  - correlation_id обязателен в каждом логе
 */
final readonly class UserBehaviorAnalyzerService
{
    /** Пороги определения "нового" пользователя */
    private const NEW_USER_MAX_DAYS = 7;
    private const NEW_USER_MAX_SESSIONS = 3;
    private const NEW_USER_MIN_SPEND = 0;

    public function __construct(
        private readonly Request $request,
        private AnonymizationService    $anonymizer,
        private BigDataAggregatorService $bigData,
        private UserTasteAnalyzerService $tasteAnalyzer,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    ) {}

    // ─── Классификация ───────────────────────────────────────────────────────

    /**
     * Определяет тип пользователя: 'new' или 'returning'.
     *
     * Критерии NEW:
     *  - Возраст аккаунта ≤ 7 дней И кол-во заказов = 0
     *
     * Критерии RETURNING:
     *  - Возраст > 7 дней ИЛИ хотя бы 1 заказ
     */
    public function classifyUser(int $userId): string
    {
        /** @var User $user */
        $user = User::findOrFail($userId);

        $daysOld  = (int) now()->diffInDays($user->created_at);
        $hasOrder = $user->orders()->whereIn('status', ['completed', 'delivered'])->exists();

        if ($daysOld <= self::NEW_USER_MAX_DAYS && ! $hasOrder) {
            return 'new';
        }

        return 'returning';
    }

    /**
     * Проверяет, является ли пользователь "новым".
     */
    public function isNewUser(int $userId): bool
    {
        return $this->classifyUser($userId) === 'new';
    }

    // ─── Обработка событий ───────────────────────────────────────────────────

    /**
     * Обработать поведенческое событие.
     *
     * @param  array{
     *   user_id: int,
     *   timestamp?: string,
     *   vertical: string,
     *   action: string,
     *   session_duration?: int,
     *   device_type?: string,
     *   city?: string,
     *   lat?: float,
     *   lon?: float,
     *   correlation_id: string,
     * } $rawEvent
     */
    public function processEvent(int $userId, array $rawEvent): void
    {
        $rawEvent['user_id']    = $userId;
        $rawEvent['timestamp']  = $rawEvent['timestamp'] ?? now()->toIso8601String();

        $isNew = $this->classifyUser($userId) === 'new';

        // 1. Обезличиваем — user_id уходит, anonymized_user_id появляется
        $anonymized = $this->anonymizer->anonymizeEvent($rawEvent);

        // 2. Пишем в ClickHouse (только anonymized данные)
        $this->bigData->insertAnonymizedEvent($anonymized);

        // 3. Обновляем taste_profile только для returning-пользователей
        //    (у новых ещё нет истории для аггрегации)
        if (! $isNew) {
            $user = User::find($userId);
            if ($user !== null) {
                $this->tasteAnalyzer->analyzeAndSaveUserProfile($user);
            }
        }

        // 4. Онлайн-обучение: 5% событий отправляем в MLRecalculateJob
        if (random_int(1, 100) <= 5) {
            MLRecalculateJob::dispatch($userId, $isNew)->onQueue('ml');
        }

        $this->logger->channel('audit')->info('Behavior event processed', [
            'user_type'      => $isNew ? 'new' : 'returning',
            'vertical'       => $rawEvent['vertical'],
            'action'         => $rawEvent['action'],
            'anonymized'     => true,
            'correlation_id' => $rawEvent['correlation_id'],
        ]);
    }

    // ─── Counts для дашборда (tenant-aware) ─────────────────────────────────

    public function getNewUsersCount(int $tenantId, string $period = '30d'): int
    {
        $days = (int) rtrim($period, 'd');

        return (int) User::where('tenant_id', $tenantId)
            ->where('created_at', '>=', now()->subDays($days))
            ->count();
    }

    public function getReturningUsersCount(int $tenantId, string $period = '30d'): int
    {
        $days = (int) rtrim($period, 'd');

        return (int) User::where('tenant_id', $tenantId)
            ->where('created_at', '<', now()->subDays(self::NEW_USER_MAX_DAYS))
            ->whereHas('orders', fn ($q) => $q->where('created_at', '>=', now()->subDays($days)))
            ->count();
    }

    // ─── Behavior patterns (для AI-конструкторов и targeting) ───────────────

    /**
     * Получить поведенческий профиль пользователя.
     * Возвращает обезличенные aggregated данные — без raw user_id.
     */
    public function getPattern(int $userId, bool $isNew): array
    {
        if ($isNew) {
            return $this->getNewUserPattern($userId);
        }

        return $this->getReturningUserPattern($userId);
    }

    private function getNewUserPattern(int $userId): array
    {
        $user = User::findOrFail($userId);

        return [
            'type'            => 'new',
            'days_since_reg'  => (int) now()->diffInDays($user->created_at),
            'device_type'     => $this->request->header('X-Device-Type', 'unknown'),
            'first_vertical'  => null,   // заполнится после первого события
            'ar_used'         => false,
            'ai_used'         => false,
        ];
    }

    private function getReturningUserPattern(int $userId): array
    {
        $user = User::findOrFail($userId);

        $lastOrder = $user->orders()->latest()->first();

        return [
            'type'                        => 'returning',
            'days_since_last_activity'    => $lastOrder
                ? (int) now()->diffInDays($lastOrder->created_at)
                : 999,
            'total_orders'                => $user->orders()->count(),
            'total_spent'                 => (float) $user->orders()
                ->whereIn('status', ['completed', 'delivered'])
                ->sum('total_amount'),
            'is_churn_risk'               => $this->isChurnRisk($userId),
            'favorite_verticals'          => $this->getFavoriteVerticals($userId),
        ];
    }

    private function isChurnRisk(int $userId): bool
    {
        $user      = User::findOrFail($userId);
        $lastOrder = $user->orders()->latest()->first();

        if ($lastOrder === null) {
            return false;
        }

        return now()->diffInDays($lastOrder->created_at) > 14;
    }

    private function getFavoriteVerticals(int $userId): array
    {
        return $this->db->table('orders')
            ->where('user_id', $userId)
            ->whereIn('status', ['completed', 'delivered'])
            ->select('vertical', $this->db->raw('count(*) as cnt'))
            ->groupBy('vertical')
            ->orderByDesc('cnt')
            ->limit(3)
            ->pluck('vertical')
            ->toArray();
    }
}
