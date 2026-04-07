<?php declare(strict_types=1);

namespace App\Services\Analytics;


use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\CacheManager;

final readonly class CustomerLifetimeValueMLService
{
    public function __construct(
        private readonly Request $request,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
        private readonly CacheManager $cache,
    ) {}

    private const CACHE_TTL = 86400; // 24 часа
        private const CHURN_THRESHOLD = 0.7; // 70% вероятности = клиент в риске

        /**
         * Вычисляет LTV для пользователя (сумма, которую он потратит за всё время)
         *
         * @param int $userId
         * @return float
         */
        public function calculateUserLTV(int $userId): float
        {
            $cacheKey = "ltv:user:{$userId}";

            return $this->cache->remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
                try {
                    // Получаем исторические данные
                    $history = $this->getUserPurchaseHistory($userId);

                    if (empty($history)) {
                        return 0.0;
                    }

                    // Вычисляем среднюю трату в месяц
                    $monthlyAvg = $this->calculateMonthlyAverage($history);

                    // Вычисляем вероятность retention (удержания)
                    $retentionRate = $this->calculateRetentionRate($userId, $history);

                    // Прогнозируем количество месяцев, в течение которых клиент останется
                    $expectedMonths = $this->predictCustomerLifetime($userId, $retentionRate);

                    // LTV = среднемесячная трата * ожидаемое количество месяцев
                    $ltv = $monthlyAvg * $expectedMonths;

                    return max(0.0, $ltv);

                } catch (\Throwable $e) {
                    $this->logger->channel('analytics_errors')->error('LTV calculation failed', [
                        'user_id' => $userId,
                        'error' => $e->getMessage(),
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
                    return 0.0;
                }
            });
        }

        /**
         * Прогнозирует вероятность оттока (churn) для пользователя
         * Возвращает вероятность 0-1 (1.0 = 100% вероятность ухода в ближайший месяц)
         *
         * @param int $userId
         * @return array {churn_probability, churn_risk_level, days_until_churn, reason}
         */
        public function predictChurnProbability(int $userId): array
        {
            try {
                $history = $this->getUserPurchaseHistory($userId);

                if (empty($history)) {
                    return [
                        'churn_probability' => 0.0,
                        'churn_risk_level' => 'unknown',
                        'days_until_churn' => null,
                        'reason' => 'New customer',
                    ];
                }

                // Извлекаем признаки для churn-модели
                $features = $this->extractChurnFeatures($userId, $history);

                // Вычисляем churn-скор (0-1)
                $churnScore = $this->computeChurnScore($features);

                // Определяем риск
                $riskLevel = $this->getRiskLevel($churnScore);

                return [
                    'churn_probability' => round($churnScore, 3),
                    'churn_risk_level' => $riskLevel,
                    'days_until_churn' => (int)($features['days_since_last_purchase'] + 30),
                    'reason' => $this->getChurnReason($features),
                ];

            } catch (\Throwable $e) {
                $this->logger->channel('analytics_errors')->error('Churn prediction failed', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
                return [
                    'churn_probability' => 0.0,
                    'churn_risk_level' => 'unknown',
                    'days_until_churn' => null,
                    'reason' => 'Analysis failed',
                ];
            }
        }

        /**
         * Сегментирует пользователей по LTV и риску оттока
         *
         * @param int $tenantId
         * @return array {high_value, at_risk, dormant, active, new}
         */
        public function segmentCustomersByValue(int $tenantId): array
        {
            $users = $this->db->table('users')
                ->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->pluck('id');

            $segments = [
                'high_value' => [],      // LTV > 50K, low churn
                'medium_value' => [],    // LTV 10-50K
                'low_value' => [],       // LTV < 10K
                'at_risk' => [],         // High churn probability
                'dormant' => [],         // No purchases in 90 days
                'new' => [],             // < 7 days old
            ];

            foreach ($users as $userId) {
                $ltv = $this->calculateUserLTV($userId);
                $churn = $this->predictChurnProbability($userId);
                $daysActive = $this->getUserAgeInDays($userId);

                if ($daysActive < 7) {
                    $segments['new'][] = $userId;
                } elseif ($churn['churn_probability'] > self::CHURN_THRESHOLD) {
                    $segments['at_risk'][] = $userId;
                } elseif ($daysActive > 90 && $ltv === 0.0) {
                    $segments['dormant'][] = $userId;
                } elseif ($ltv > 50000) {
                    $segments['high_value'][] = $userId;
                } elseif ($ltv > 10000) {
                    $segments['medium_value'][] = $userId;
                } else {
                    $segments['low_value'][] = $userId;
                }
            }

            return $segments;
        }

        /**
         * Получает историю покупок пользователя
         *
         * @param int $userId
         * @return array
         */
        private function getUserPurchaseHistory(int $userId): array
        {
            $purchases = $this->db->table('orders')
                ->where('user_id', $userId)
                ->where('status', 'completed')
                ->orderBy('created_at')
                ->pluck('total_price', 'created_at')
                ->toArray();

            return $purchases;
        }

        /**
         * Вычисляет среднюю трату в месяц
         *
         * @param array $purchaseHistory
         * @return float
         */
        private function calculateMonthlyAverage(array $purchaseHistory): float
        {
            $totalSpent = array_sum($purchaseHistory);

            if (empty($purchaseHistory)) {
                return 0.0;
            }

            $firstDate = min(array_keys($purchaseHistory));
            $lastDate = max(array_keys($purchaseHistory));

            $monthsDiff = (int)\Carbon\Carbon::parse($lastDate)
                ->diffInMonths(\Carbon\Carbon::parse($firstDate));

            if ($monthsDiff === 0) {
                $monthsDiff = 1;
            }

            return $totalSpent / $monthsDiff;
        }

        /**
         * Вычисляет коэффициент удержания (0-1)
         *
         * @param int $userId
         * @param array $history
         * @return float
         */
        private function calculateRetentionRate(int $userId, array $history): float
        {
            $purchaseDates = array_keys($history);

            if (count($purchaseDates) < 2) {
                return 0.5; // Нейтральный коэффициент для новых
            }

            // Вычисляем интервалы между покупками
            $intervals = [];
            for ($i = 1; $i < count($purchaseDates); $i++) {
                $prev = \Carbon\Carbon::parse($purchaseDates[$i - 1]);
                $current = \Carbon\Carbon::parse($purchaseDates[$i]);
                $intervals[] = $current->diffInDays($prev);
            }

            $avgInterval = array_sum($intervals) / count($intervals);

            // Если средний интервал < 30 дней = хороший retention
            if ($avgInterval < 30) {
                return 0.9;
            } elseif ($avgInterval < 60) {
                return 0.7;
            } elseif ($avgInterval < 90) {
                return 0.5;
            }

            return 0.2;
        }

        /**
         * Прогнозирует, сколько месяцев клиент будет активен
         *
         * @param int $userId
         * @param float $retentionRate
         * @return float
         */
        private function predictCustomerLifetime(int $userId, float $retentionRate): float
        {
            $accountAgeMonths = \Carbon\Carbon::parse(
                $this->db->table('users')->find($userId)->created_at
            )->diffInMonths(now());

            // Формула: средняя жизненная ценность клиента = месяцы активности
            // Если retention = 0.9, то в среднем клиент будет активен ~10 месяцев
            $expectedLifetime = $accountAgeMonths + (36 * $retentionRate); // 36 = 3 года

            return max(1, min(60, $expectedLifetime)); // Капируем от 1 до 60 месяцев
        }

        /**
         * Извлекает признаки для churn-модели
         *
         * @param int $userId
         * @param array $history
         * @return array
         */
        private function extractChurnFeatures(int $userId, array $history): array
        {
            $now = now();
            $lastPurchaseDate = max(array_keys($history));
            $daysSinceLastPurchase = (int)$now->diffInDays(\Carbon\Carbon::parse($lastPurchaseDate));

            $last30Days = $now->copy()->subDays(30)->startOfDay();
            $purchasesLast30Days = $this->db->table('orders')
                ->where('user_id', $userId)
                ->where('created_at', '>=', $last30Days)
                ->count();

            $totalPurchases = count($history);
            $avgOrderValue = array_sum($history) / $totalPurchases;

            return [
                'days_since_last_purchase' => $daysSinceLastPurchase,
                'purchases_last_30d' => $purchasesLast30Days,
                'total_purchases' => $totalPurchases,
                'avg_order_value' => $avgOrderValue,
                'account_age_days' => $this->getUserAgeInDays($userId),
                'total_spent' => (float)array_sum($history),
            ];
        }

        /**
         * Вычисляет churn-скор на основе извлечённых признаков
         *
         * @param array $features
         * @return float
         */
        private function computeChurnScore(array $features): float
        {
            $score = 0.0;

            // Давно не покупал
            if ($features['days_since_last_purchase'] > 90) {
                $score += 0.4;
            } elseif ($features['days_since_last_purchase'] > 30) {
                $score += 0.2;
            }

            // Нет покупок в последний месяц
            if ($features['purchases_last_30d'] === 0) {
                $score += 0.3;
            }

            // Всего одна покупка (высокий риск первого захода)
            if ($features['total_purchases'] === 1) {
                $score += 0.2;
            }

            // Молодой аккаунт с низкой тратой
            if ($features['account_age_days'] < 30 && $features['total_spent'] < 1000) {
                $score += 0.15;
            }

            return min(1.0, $score);
        }

        /**
         * Определяет уровень риска
         *
         * @param float $churnScore
         * @return string
         */
        private function getRiskLevel(float $churnScore): string
        {
            if ($churnScore > 0.7) {
                return 'critical';
            } elseif ($churnScore > 0.5) {
                return 'high';
            } elseif ($churnScore > 0.3) {
                return 'medium';
            }

            return 'low';
        }

        /**
         * Генерирует причину прогнозируемого оттока
         *
         * @param array $features
         * @return string
         */
        private function getChurnReason(array $features): string
        {
            if ($features['days_since_last_purchase'] > 90) {
                return 'No purchases in last 90 days';
            } elseif ($features['purchases_last_30d'] === 0) {
                return 'No purchases in last 30 days';
            } elseif ($features['total_purchases'] === 1) {
                return 'Only one purchase on record';
            }

            return 'Declining purchase frequency';
        }

        /**
         * Получает возраст аккаунта в днях
         *
         * @param int $userId
         * @return int
         */
        private function getUserAgeInDays(int $userId): int
        {
            $createdAt = $this->db->table('users')->find($userId)?->created_at;

            if (!$createdAt) {
                return 0;
            }

            return (int)now()->diffInDays(\Carbon\Carbon::parse($createdAt));
        }
}
