<?php

namespace App\Domains\Finances\Services\Security;

use App\Domains\Finances\Models\PaymentTransaction;
use Illuminate\Support\Facades\{Cache, Log};

/**
 * Сервис управления правилами и лимитами для защиты от мошенничества.
 */
class FraudControlService
{
    /**
     * Оценить риск операции пользователя (от 0 до 100).
     * 
     * @param \App\Models\User $user Пользователь
     * @param array $context Контекст операции: ['amount', 'type', 'location', etc.]
     * @return int Оценка риска от 0 до 100
     */
    public function assessRisk($user, array $context = []): int
    {
        try {
            $riskScore = 0;

            // 1. Проверка суммы операции
            if (!empty($context['amount'])) {
                $dailyLimit = config('finances.fraud.daily_limit', 100000);
                if ($context['amount'] > $dailyLimit * 0.5) {
                    $riskScore += 20;
                }
            }

            // 2. Проверка дневного лимита
            $dailyTotal = $this->getUserDailyTotal($user->id);
            if ($dailyTotal > config('finances.fraud.daily_limit', 100000) * 0.8) {
                $riskScore += 25;
            }

            // 3. Проверка количества операций
            $txCount = $this->getUserTransactionCount($user->id);
            if ($txCount > config('finances.fraud.max_daily_transactions', 10)) {
                $riskScore += 20;
            }

            // 4. Проверка истории пользователя
            if ($user->created_at->diffInDays() < 7) {
                $riskScore += 15; // Новый пользователь - повышенный риск
            }

            // 5. Проверка повторных операций
            if (!empty($context['type'])) {
                $recentCount = PaymentTransaction::where('user_id', $user->id)
                    ->where('type', $context['type'])
                    ->whereDate('created_at', now())
                    ->count();
                
                if ($recentCount > 5) {
                    $riskScore += 15;
                }
            }

            // 6. Необычное время (3:00-5:00 AM)
            $hour = (int)now()->format('H');
            if ($hour >= 3 && $hour <= 5) {
                $riskScore += 10;
            }

            return min($riskScore, 100);
        } catch (\Exception $e) {
            Log::warning('Risk assessment failed', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);
            return 50; // При ошибке - средний риск
        }
    }

    /**
     * Проверить транзакцию на предмет мошенничества.
     */
    public function checkTransaction(array $transaction, int $userId): array
    {
        try {
            $riskScore = 0;
            $reasons = [];

            // 1. Проверка лимита по сумме
            $dailyLimit = config('finances.fraud.daily_limit', 100000);
            if ($transaction['amount'] > $dailyLimit) {
                $riskScore += 30;
                $reasons[] = 'Amount exceeds daily limit';
            }

            // 2. Проверка дневной суммы
            $dailyTotal = $this->getUserDailyTotal($userId);
            if ($dailyTotal + $transaction['amount'] > $dailyLimit * 2) {
                $riskScore += 25;
                $reasons[] = 'Daily accumulation exceeds limit';
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
                Log::warning('ML fraud check failed', ['error' => $e->getMessage()]);
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

            Log::info('Fraud check completed', [
                'user_id' => $userId,
                'risk_score' => $finalScore,
                'action' => $result['block'] ? 'BLOCK' : ($result['require_2fa'] ? '2FA' : 'ALLOW'),
                'amount' => $transaction['amount'],
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('Fraud check failed', ['error' => $e->getMessage(), 'user_id' => $userId]);
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
        return Cache::remember(
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
        return Cache::remember(
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
        return Cache::remember(
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
        // Простая проверка: если расстояние > 1000 км и разница < 2 часа
        // то это предполагает быстрое перемещение (невозможное для человека)
        return false; // TODO: Реализовать полноценную проверку
    }

    /**
     * Заблокировать пользователя на время.
     */
    public function blockUser(int $userId, int $hours = 24): void
    {
        Cache::put("fraud_block_{$userId}", true, now()->addHours($hours));
        Log::warning('User temporarily blocked due to fraud suspicion', [
            'user_id' => $userId,
            'duration_hours' => $hours,
        ]);
    }

    /**
     * Проверить заблокирован ли пользователь.
     */
    public function isUserBlocked(int $userId): bool
    {
        return Cache::has("fraud_block_{$userId}");
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
            ],
            'config' => [
                'daily_limit' => config('finances.fraud.daily_limit'),
                'max_daily_transactions' => config('finances.fraud.max_daily_transactions'),
            ],
        ];
    }
}
