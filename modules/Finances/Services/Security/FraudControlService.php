<?php

declare(strict_types=1);

namespace App\Domains\Finances\Services\Security;

use App\Domains\Finances\Models\PaymentTransaction;
use Illuminate\Support\Facades\{Cache, Log, DB};

/**
 * Сервис управления правилами и лимитами для защиты от мошенничества.
 * Согласно КАНОН 2026: check(), checkPayment(), checkWithdrawal(), checkReferral().
 */
final class FraudControlService
{
    /**
     * Универсальный метод проверки операции.
     * Согласно КАНОН 2026: ОБЯЗАТЕЛЕН перед любой критичной операцией.
     *
     * @return array['allowed' => bool, 'score' => float 0-1, 'reason' => ?string]
     */
    public function check(
        int $userId,
        string $operationType,
        int $amount,
        array $context = []
    ): array {
        try {
            $score = $this->calculateScore($userId, $operationType, $amount, $context);
            $threshold = $this->getThreshold($operationType);

            $allowed = $score < $threshold;
            $reason = null;

            if (!$allowed) {
                $reason = $this->getBlockReason($score, $operationType, $context);

                // Логирование попытки фрода
                $this->logFraudAttempt($userId, $operationType, $amount, $score, 'block', $reason, $context);
            }

            $this->log->channel('audit')->info('Fraud check completed', [
                'user_id' => $userId,
                'operation_type' => $operationType,
                'amount' => $amount,
                'score' => $score,
                'threshold' => $threshold,
                'allowed' => $allowed,
            ]);

            return [
                'allowed' => $allowed,
                'score' => $score,
                'reason' => $reason,
            ];
        } catch (\Throwable $e) {
            $this->log->error('Fraud check failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            // Fallback: при ошибке проверки — осторожный подход
            return [
                'allowed' => false,
                'score' => 0.8,
                'reason' => 'Fraud check service error',
            ];
        }
    }

    /**
     * Проверка конкретно для платежей.
     * Согласно КАНОН 2026: ОБЯЗАТЕЛЕН перед инициализацией платежа.
     *
     * @return array['allowed' => bool, 'score' => float, 'reason' => ?string]
     */
    public function checkPayment(int $userId, int $amount, array $context = []): array
    {
        return $this->check($userId, 'payment', $amount, array_merge($context, [
            'type' => 'payment',
        ]));
    }

    /**
     * Проверка для вывода средств (withdrawal / payout).
     *
     * @return array
     */
    public function checkWithdrawal(int $userId, int $amount, array $context = []): array
    {
        return $this->check($userId, 'withdrawal', $amount, array_merge($context, [
            'type' => 'withdrawal',
        ]));
    }

    /**
     * Проверка для реферальных бонусов.
     *
     * @return array
     */
    public function checkReferral(int $userId, int $referredUserId, array $context = []): array
    {
        return $this->check($userId, 'referral', 0, array_merge($context, [
            'referred_user_id' => $referredUserId,
        ]));
    }

    /**
     * Проверка для промо-кодов.
     *
     * @return array
     */
    public function checkPromoAbuse(int $userId, string $promoCode, int $amount, array $context = []): array
    {
        return $this->check($userId, 'promo_apply', $amount, array_merge($context, [
            'promo_code' => $promoCode,
        ]));
    }

    /**
     * Рассчитать score фрода для операции (0-1, где 1 = 100% фрод).
     */
    private function calculateScore(
        int $userId,
        string $operationType,
        int $amount,
        array $context = []
    ): float {
        $score = 0.0;

        // Фактор 1: Сумма операции (максимум 0.3)
        $dailyLimit = config('finances.fraud.daily_limit', 10000000); // 100 000 РУБ в копейках
        if ($amount > $dailyLimit) {
            $score += 0.3;
        } elseif ($amount > $dailyLimit / 2) {
            $score += 0.15;
        }

        // Фактор 2: Частота операций (максимум 0.25)
        $dailyCount = $this->getUserOperationCount($userId, $operationType);
        if ($dailyCount > 10) {
            $score += 0.25;
        } elseif ($dailyCount > 5) {
            $score += 0.15;
        }

        // Фактор 3: Возраст аккаунта (максимум 0.2)
        $accountAge = $this->getAccountAgeDays($userId);
        if ($accountAge < 7) {
            $score += 0.2;
        } elseif ($accountAge < 30) {
            $score += 0.1;
        }

        // Фактор 4: История успешных операций (максимум -0.15, снижает скор)
        $successRate = $this->getSuccessRate($userId, $operationType);
        if ($successRate > 0.95) {
            $score -= 0.15; // Надежный пользователь
        }

        // Фактор 5: Необычное время (максимум 0.1)
        $hour = (int) now()->format('H');
        if ($hour >= 2 && $hour <= 5) { // Ночное время
            $score += 0.1;
        }

        // Фактор 6: IP/Device изменения (максимум 0.15)
        if ($this->isNewDevice($userId) || $this->hasIpChange($userId)) {
            $score += 0.15;
        }

        return min(max($score, 0.0), 1.0); // Нормализовать к 0-1
    }

    /**
     * Получить порог блокировки для типа операции.
     */
    private function getThreshold(string $operationType): float
    {
        return match ($operationType) {
            'payment' => 0.8,
            'withdrawal' => 0.7,
            'payout' => 0.6,
            'referral' => 0.9,
            'promo_apply' => 0.85,
            default => 0.75,
        };
    }

    /**
     * Получить текстовое объяснение блокировки.
     */
    private function getBlockReason(float $score, string $operationType, array $context = []): string
    {
        if ($score > 0.95) {
            return 'High fraud risk detected';
        } elseif ($score > 0.85) {
            return 'Suspicious activity detected';
        } elseif ($score > 0.75) {
            return 'Operation requires additional verification';
        }

        return 'Operation blocked for security reasons';
    }

    /**
     * Логировать попытку фрода в fraud_attempts таблицу.
     */
    private function logFraudAttempt(
        int $userId,
        string $operationType,
        int $amount,
        float $score,
        string $decision,
        ?string $reason,
        array $context = []
    ): void {
        try {
            $this->db->table('fraud_attempts')->insert([
                'tenant_id' => auth()->user()->tenant_id ?? 'system',
                'user_id' => $userId,
                'operation_type' => $operationType,
                'ip_address' => request()->ip(),
                'device_fingerprint' => $this->getDeviceFingerprint(),
                'ml_score' => $score,
                'decision' => $decision,
                'reason' => $reason,
                'blocked_at' => now(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $this->log->warning('Failed to log fraud attempt', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Получить количество операций пользователя за день.
     */
    private function getUserOperationCount(int $userId, string $operationType): int
    {
        return (int) $this->db->table('balance_transactions')
            ->where('user_id', $userId)
            ->where('type', $operationType)
            ->whereDate('created_at', now())
            ->count();
    }

    /**
     * Получить возраст аккаунта в днях.
     */
    private function getAccountAgeDays(int $userId): int
    {
        $user = $this->db->table('users')->find($userId);

        if (!$user) {
            return 0;
        }

        return now()->diffInDays($user->created_at);
    }

    /**
     * Получить процент успешных операций.
     */
    private function getSuccessRate(int $userId, string $operationType): float
    {
        $total = $this->db->table('balance_transactions')
            ->where('user_id', $userId)
            ->where('type', $operationType)
            ->count();

        if ($total === 0) {
            return 0.0;
        }

        $successful = $this->db->table('balance_transactions')
            ->where('user_id', $userId)
            ->where('type', $operationType)
            ->where('status', 'completed')
            ->count();

        return $successful / $total;
    }

    /**
     * Проверить, новое ли устройство.
     */
    private function isNewDevice(int $userId): bool
    {
        $deviceFingerprint = $this->getDeviceFingerprint();

        $existingCount = $this->db->table('user_sessions')
            ->where('user_id', $userId)
            ->where('device_fingerprint', $deviceFingerprint)
            ->count();

        return $existingCount === 0;
    }

    /**
     * Проверить, изменился ли IP.
     */
    private function hasIpChange(int $userId): bool
    {
        $currentIp = request()->ip();
        $lastIp = $this->db->table('user_sessions')
            ->where('user_id', $userId)
            ->latest('created_at')
            ->first()?->ip_address;

        if (!$lastIp) {
            return true; // Первая сессия
        }

        return $currentIp !== $lastIp;
    }

    /**
     * Получить fingerprint устройства.
     */
    private function getDeviceFingerprint(): string
    {
        return hash('sha256', implode('|', [
            request()->ip(),
            request()->userAgent(),
            auth()->id() ?? 'anonymous',
        ]));
    }

            // 3. Количество транзакций за день
            $txCount = $this->getUserTransactionCount($userId);
            if ($txCount > config('finances.fraud.max_daily_transactions', 10)) {
                $riskScore += 20;
                $reasons[] = 'Too many transactions today';
            }

            // 4. Проверка геолокации (если доступна)
            if (!empty($transaction['location']) && !empty($transaction['user_ip'])) {
                if ($this->isLocationSuspicious($userId, $transaction['location'])) {
                    $riskScore += 35;
                    $reasons[] = 'Suspicious location';
                }
            }

            // 5. Необычное время суток
            $hour = date('H');
            if ($hour >= 3 && $hour <= 5) {
                $riskScore += 15;
                $reasons[] = 'Unusual transaction time';
            }

            // 6. Интеграция с ML моделью
            try {
                $mlScore = app(FraudMLService::class)->predictFraudScore($transaction, $userId);
                $riskScore = max($riskScore, $mlScore);
                $reasons[] = "ML risk: {$mlScore}";
            } catch (\Exception $e) {
                $this->log->warning('ML fraud check failed', ['error' => $e->getMessage()]);
            }

            $finalScore = min($riskScore, 100);

            $result = [
                'risk_score' => $finalScore,
                'allow' => $finalScore < 40,
                'require_verification' => $finalScore >= 40 && $finalScore < 70,
                'require_2fa' => $finalScore >= 70 && $finalScore < 85,
                'block' => $finalScore >= 85,
                'reasons' => $reasons,
            ];

            $this->log->info('Fraud check completed', [
                'user_id' => $userId,
                'risk_score' => $finalScore,
                'action' => $result['block'] ? 'BLOCK' : ($result['require_2fa'] ? '2FA' : 'ALLOW'),
                'amount' => $transaction['amount'],
            ]);

            return $result;
        } catch (\Exception $e) {
            $this->log->error('Fraud check failed', ['error' => $e->getMessage(), 'user_id' => $userId]);
            // По умолчанию требуем 2FA при ошибке проверки
            return ['risk_score' => 50, 'require_2fa' => true, 'error' => true];
        }
    }

    /**
     * Получить дневной лимит пользователя.
     */
    public function getUserDailyLimit(int $userId): float
    {
        // Может быть переопределено в профиле пользователя
        return $this->cache->remember(
            "fraud_limit_{$userId}",
            3600,
            fn() => (float) config('finances.fraud.daily_limit', 100000)
        );
    }

    /**
     * Получить дневную сумму транзакций.
     */
    private function getUserDailyTotal(int $userId): float
    {
        return $this->cache->remember(
            "fraud_daily_{$userId}",
            300,
            fn() => PaymentTransaction::where('user_id', $userId)
                ->whereDate('created_at', now())
                ->where('status', '!=', 'failed')
                ->sum('amount')
        );
    }

    /**
     * Получить количество транзакций за день.
     */
    private function getUserTransactionCount(int $userId): int
    {
        return $this->cache->remember(
            "fraud_count_{$userId}",
            300,
            fn() => PaymentTransaction::where('user_id', $userId)
                ->whereDate('created_at', now())
                ->count()
        );
    }

    /**
     * Проверить подозрительность геолокации.
     */
    private function isLocationSuspicious(int $userId, array $location): bool
    {
        // Получить последнюю активность пользователя
        $lastActivity = $this->db->table('audit_logs')
            ->where('user_id', $userId)
            ->latest('created_at')
            ->first();

        if (!$lastActivity) {
            return false; // Первая активность не может быть подозрительной
        }

        // Проверить разницу во времени и расстояние
        $lastPayload = json_decode($lastActivity->payload, true);
        $lastLocation = $lastPayload['location'] ?? null;
        $timeDiff = now()->diffInMinutes($lastActivity->created_at);

        if (!$lastLocation || !isset($lastLocation['latitude'], $lastLocation['longitude'])) {
            return false;
        }

        // Вычислить расстояние между двумя локациями (формула Хаверсина)
        $distance = $this->calculateDistance(
            $lastLocation['latitude'], $lastLocation['longitude'],
            $location['latitude'] ?? 0, $location['longitude'] ?? 0
        );

        // Если расстояние > 1000 км и время < 2 часа, это невозможно
        // (максимальная скорость самолета ~900 км/ч)
        $maxPossibleDistance = (900 / 60) * $timeDiff; // км
        
        return $distance > $maxPossibleDistance;
    }

    /**
     * Рассчитать расстояние между двумя координатами (Haversine formula).
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // км
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    /**
     * Заблокировать пользователя на время.
     */
    public function blockUser(int $userId, int $hours = 24): void
    {
        $this->cache->put("fraud_block_{$userId}", true, now()->addHours($hours));
        $this->log->warning('User temporarily blocked due to fraud suspicion', [
            'user_id' => $userId,
            'duration_hours' => $hours,
        ]);
    }

    /**
     * Проверить заблокирован ли пользователь.
     */
    public function isUserBlocked(int $userId): bool
    {
        return $this->cache->has("fraud_block_{$userId}");
    }

    /**
     * Проверка манипуляции wishlist для поиска.
     * Согласно КАНОН 2026: выявлять попытки специально добавлять/удалять товары для манипуляции выдачей.
     *
     * @param int $userId ID пользователя
     * @param int $productId ID товара
     * @param string $correlationId Идентификатор корреляции
     * @return array['allowed' => bool, 'score' => float, 'reason' => ?string]
     */
    public function checkWishlistManipulation(
        int $userId,
        int $productId,
        string $correlationId = ''
    ): array {
        try {
            // Подсчитываем количество add/remove операций в последний час
            $recentActions = $this->cache->get("wishlist_actions_{$userId}_{$productId}", []);

            if (count($recentActions) > 5) {
                // Более 5 операций за час = подозрительно
                $score = 0.85;
                $reason = 'Suspicious wishlist manipulation detected';

                $this->log->channel('audit')->warning('Wishlist manipulation suspected', [
                    'user_id' => $userId,
                    'product_id' => $productId,
                    'actions_count' => count($recentActions),
                    'correlation_id' => $correlationId,
                ]);

                return [
                    'allowed' => false,
                    'score' => $score,
                    'reason' => $reason,
                ];
            }

            return [
                'allowed' => true,
                'score' => 0.2,
                'reason' => null,
            ];
        } catch (\Throwable $e) {
            $this->log->error('Wishlist manipulation check failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return [
                'allowed' => true,
                'score' => 0.3,
                'reason' => null,
            ];
        }
    }

    /**
     * Проверка бонусов.
     */
    public function checkBonus(
        int $tenantId,
        int $recipientId,
        int $amountCopeki,
        string $correlationId = ''
    ): array {
        // ML-скоринг для бонусных операций
        $features = $this->extractBonusFeatures($tenantId, $recipientId, $amountCopeki);
        $mlResult = $this->fraudMLService->scoreOperation($features, 'bonus_award', $correlationId);
        
        $allowed = $mlResult['decision'] !== 'block';
        
        return [
            'allowed' => $allowed,
            'score' => $mlResult['score'],
            'reason' => $allowed ? null : 'Suspicious bonus activity detected',
            'ml_decision' => $mlResult['decision'],
        ];
    }

    /**
     * Проверка выплаты.
     */
    public function checkPayout(
        int $tenantId,
        int $amountCopeki,
        string $correlationId = ''
    ): array {
        // ML-скоринг для выплат
        $features = $this->extractPayoutFeatures($tenantId, $amountCopeki);
        $mlResult = $this->fraudMLService->scoreOperation($features, 'payout', $correlationId);
        
        $allowed = $mlResult['decision'] !== 'block';
        
        return [
            'allowed' => $allowed,
            'score' => $mlResult['score'],
            'reason' => $allowed ? null : 'Suspicious payout activity detected',
            'ml_decision' => $mlResult['decision'],
        ];
    }

    /**
     * Извлекает признаки для бонусной операции.
     */
    private function extractBonusFeatures(int $tenantId, int $recipientId, int $amountCopeki): array
    {
        return [
            'amount' => $amountCopeki,
            'tenant_id' => $tenantId,
            'recipient_id' => $recipientId,
            'bonus_count_today' => \$this->db->table('bonus_transactions')
                ->where('recipient_id', $recipientId)
                ->whereDate('created_at', today())
                ->count(),
            'total_bonus_today' => \$this->db->table('bonus_transactions')
                ->where('recipient_id', $recipientId)
                ->whereDate('created_at', today())
                ->sum('amount') ?? 0,
        ];
    }

    /**
     * Извлекает признаки для выплаты.
     */
    private function extractPayoutFeatures(int $tenantId, int $amountCopeki): array
    {
        return [
            'amount' => $amountCopeki,
            'tenant_id' => $tenantId,
            'payout_count_today' => \$this->db->table('payment_transactions')
                ->where('tenant_id', $tenantId)
                ->where('type', 'payout')
                ->whereDate('created_at', today())
                ->count(),
            'total_payout_today' => \$this->db->table('payment_transactions')
                ->where('tenant_id', $tenantId)
                ->where('type', 'payout')
                ->whereDate('created_at', today())
                ->sum('amount') ?? 0,
        ];
    }

    /**
     * Получить информацию о сервисе.
     */
    public function getInfo(): array
    {
        return [
            'name' => 'FraudControlService',
            'version' => '1.0',
            'features' => [
                'daily_limit_check',
                'transaction_count_check',
                'location_check',
                'time_check',
                'ml_integration',
                'wishlist_manipulation_detection',
            ],
            'config' => [
                'daily_limit' => config('finances.fraud.daily_limit'),
                'max_daily_transactions' => config('finances.fraud.max_daily_transactions'),
            ],
        ];
    }
}
