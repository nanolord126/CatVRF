<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ML-based Fraud Detection Service
 * Обнаружение аномалий и мошеннических действий через ML
 * 
 * @package App\Services\Analytics
 * @category ML / Fraud Detection
 */
final class FraudDetectionMLService
{
    private const CACHE_TTL = 3600; // 1 час
    private const ANOMALY_THRESHOLD = 0.75; // 75% confidence = мошенничество
    private const MIN_SAMPLES = 20; // Минимум примеров для обучения

    /**
     * Анализирует попытку платежа и возвращает ML-скор мошенничества (0-1)
     * 
     * @param int $userId
     * @param float $amount
     * @param string $deviceFingerprint
     * @param string $ipAddress
     * @param string $correlationId
     * @return array {score: 0-1, isBlocked: bool, reason: string, features: array}
     */
    public function scorePaymentAttempt(
        int $userId,
        float $amount,
        string $deviceFingerprint,
        string $ipAddress,
        string $correlationId
    ): array {
        try {
            // Получаем исторические данные пользователя
            $userHistory = $this->getUserPaymentHistory($userId);
            
            // Извлекаем фичи (признаки)
            $features = $this->extractPaymentFeatures(
                $userId,
                $amount,
                $deviceFingerprint,
                $ipAddress,
                $userHistory
            );

            // Вычисляем ML-скор
            $score = $this->computeAnomalyScore($features);

            // Определяем решение
            $isBlocked = $score >= self::ANOMALY_THRESHOLD;

            Log::channel('audit')->info('Payment fraud score calculated', [
                'user_id' => $userId,
                'correlation_id' => $correlationId,
                'score' => round($score, 3),
                'is_blocked' => $isBlocked,
                'amount' => $amount,
                'feature_count' => count($features)
            ]);

            return [
                'score' => $score,
                'isBlocked' => $isBlocked,
                'reason' => $this->getBlockReason($score, $features),
                'features' => $features,
                'confidence' => min(0.99, $score + 0.1)
            ];

        } catch (\Throwable $e) {
            Log::channel('analytics_errors')->error('Fraud detection failed', [
                'user_id' => $userId,
                'correlation_id' => $correlationId,
                'error' => $e->getMessage()
            ]);

            return [
                'score' => 0.0,
                'isBlocked' => false,
                'reason' => 'Fraud detection unavailable',
                'features' => [],
                'confidence' => 0.0
            ];
        }
    }

    /**
     * Извлекает 30+ признаков (фичи) для ML-модели
     * 
     * @param int $userId
     * @param float $amount
     * @param string $deviceFingerprint
     * @param string $ipAddress
     * @param array $userHistory
     * @return array
     */
    private function extractPaymentFeatures(
        int $userId,
        float $amount,
        string $deviceFingerprint,
        string $ipAddress,
        array $userHistory
    ): array {
        $now = now();
        $today = $now->startOfDay();
        $last7Days = $now->copy()->subDays(7)->startOfDay();
        $last30Days = $now->copy()->subDays(30)->startOfDay();

        // Получаем платежи за периоды
        $payments1Min = $this->getPaymentCount($userId, $now->copy()->subMinute(1));
        $payments5Min = $this->getPaymentCount($userId, $now->copy()->subMinutes(5));
        $payments1Hour = $this->getPaymentCount($userId, $now->copy()->subHour(1));
        $payments1Day = $this->getPaymentCount($userId, $today);
        $payments7Day = $this->getPaymentCount($userId, $last7Days);
        $payments30Day = $this->getPaymentCount($userId, $last30Days);

        // Сумма платежей
        $sum1Day = $this->getPaymentSum($userId, $today);
        $sum7Day = $this->getPaymentSum($userId, $last7Days);
        $sum30Day = $this->getPaymentSum($userId, $last30Days);

        // Среднее значение платежа
        $avgAmount30Day = $payments30Day > 0 ? $sum30Day / $payments30Day : 0;
        $avgAmount7Day = $payments7Day > 0 ? $sum7Day / $payments7Day : 0;

        return [
            // Временные признаки (8)
            'payments_1_min' => $payments1Min,
            'payments_5_min' => $payments5Min,
            'payments_1_hour' => $payments1Hour,
            'payments_1_day' => $payments1Day,
            'payments_7_day' => $payments7Day,
            'payments_30_day' => $payments30Day,
            'hour_of_day' => (int)$now->format('H'),
            'day_of_week' => (int)$now->format('w'),

            // Признаки сумм (8)
            'amount' => $amount,
            'amount_vs_avg_30d' => $avgAmount30Day > 0 ? $amount / $avgAmount30Day : 1.0,
            'amount_vs_avg_7d' => $avgAmount7Day > 0 ? $amount / $avgAmount7Day : 1.0,
            'sum_1_day' => $sum1Day,
            'sum_7_day' => $sum7Day,
            'sum_30_day' => $sum30Day,
            'max_amount_30d' => $this->getMaxPaymentAmount($userId, $last30Days),
            'min_amount_30d' => $this->getMinPaymentAmount($userId, $last30Days),

            // Признаки устройства и IP (6)
            'device_changes_7d' => $this->getDeviceChanges($userId, $last7Days),
            'ip_changes_7d' => $this->getIpChanges($userId, $last7Days),
            'new_device' => $this->isNewDevice($userId, $deviceFingerprint) ? 1 : 0,
            'new_ip' => $this->isNewIp($userId, $ipAddress) ? 1 : 0,
            'account_age_days' => (int)$userHistory['account_age_days'] ?? 0,
            'device_trust_score' => $this->getDeviceTrustScore($userId, $deviceFingerprint),

            // Признаки история (8)
            'successful_payments_30d' => $userHistory['successful_payments_30d'] ?? 0,
            'failed_payments_30d' => $userHistory['failed_payments_30d'] ?? 0,
            'chargeback_count_ever' => $userHistory['chargeback_count_ever'] ?? 0,
            'refund_count_30d' => $userHistory['refund_count_30d'] ?? 0,
            'decline_rate_30d' => $userHistory['decline_rate_30d'] ?? 0.0,
            'avg_days_between_payments' => $userHistory['avg_days_between_payments'] ?? 30,
            'customer_lifetime_value' => $userHistory['lifetime_value'] ?? 0,
            'is_first_payment' => $userHistory['total_payments'] === 0 ? 1 : 0,
        ];
    }

    /**
     * Вычисляет аномалийность на основе извлечённых фич (правильно-основанный скоринг)
     *
     * @param array $features
     * @return float
     */
    private function computeAnomalyScore(array $features): float
    {
        $score = 0.0;

        // Много платежей за короткий период
        if ($features['payments_1_min'] > 0) $score += 0.15;
        if ($features['payments_5_min'] > 3) $score += 0.20;
        if ($features['payments_1_hour'] > 10) $score += 0.15;

        // Необычная сумма
        if ($features['amount_vs_avg_30d'] > 5.0) $score += 0.20;
        if ($features['amount_vs_avg_30d'] > 10.0) $score += 0.15;

        // Новое устройство/IP
        if ($features['new_device'] === 1) $score += 0.10;
        if ($features['new_ip'] === 1) $score += 0.10;

        // Много смен устройства/IP
        if ($features['device_changes_7d'] > 5) $score += 0.15;
        if ($features['ip_changes_7d'] > 10) $score += 0.15;

        // История проблем
        if ($features['chargeback_count_ever'] > 0) $score += 0.25;
        if ($features['refund_count_30d'] > 3) $score += 0.20;
        if ($features['decline_rate_30d'] > 0.30) $score += 0.20;

        // Первый платёж с большой суммой
        if ($features['is_first_payment'] === 1 && $features['amount'] > 10000) $score += 0.20;

        // Молодой аккаунт с большой суммой
        if ($features['account_age_days'] < 7 && $features['amount'] > 5000) $score += 0.15;

        // Нормируем к диапазону 0-1
        return min(1.0, $score);
    }

    /**
     * Определяет причину блокировки на основе скора и фич
     * 
     * @param float $score
     * @param array $features
     * @return string
     */
    private function getBlockReason(float $score, array $features): string
    {
        if ($score < 0.5) {
            return 'Low risk - allowed';
        } elseif ($score < 0.75) {
            return 'Medium risk - review required';
        } else {
            if ($features['chargeback_count_ever'] > 0) {
                return 'History of chargebacks detected';
            }
            if ($features['payments_5_min'] > 3) {
                return 'Rapid consecutive payments detected';
            }
            if ($features['amount_vs_avg_30d'] > 10.0) {
                return 'Amount significantly exceeds average';
            }
            if ($features['new_device'] === 1 && $features['amount'] > 5000) {
                return 'New device with large amount';
            }
            return 'Suspicious activity pattern detected';
        }
    }

    /**
     * Получает историю платежей пользователя
     * 
     * @param int $userId
     * @return array
     */
    private function getUserPaymentHistory(int $userId): array
    {
        return Cache::remember("user_payment_history:{$userId}", self::CACHE_TTL, function () use ($userId) {
            $last30Days = now()->subDays(30)->startOfDay();
            
            $payments = DB::table('balance_transactions')
                ->where('user_id', $userId)
                ->where('created_at', '>=', $last30Days)
                ->get();

            return [
                'successful_payments_30d' => $payments->where('status', 'completed')->count(),
                'failed_payments_30d' => $payments->where('status', 'failed')->count(),
                'decline_rate_30d' => $payments->count() > 0 
                    ? $payments->where('status', 'failed')->count() / $payments->count()
                    : 0,
                'refund_count_30d' => $payments->where('type', 'refund')->count(),
                'lifetime_value' => $payments->sum('amount') ?? 0,
                'total_payments' => $payments->count(),
                'account_age_days' => now()->diffInDays($this->getUserCreatedAt($userId)),
                'chargeback_count_ever' => DB::table('chargebacks')->where('user_id', $userId)->count(),
                'avg_days_between_payments' => $this->calculateAvgDaysBetweenPayments($userId),
            ];
        });
    }

    private function getPaymentCount(int $userId, \Carbon\Carbon $since): int
    {
        return DB::table('balance_transactions')
            ->where('user_id', $userId)
            ->where('created_at', '>=', $since)
            ->count();
    }

    private function getPaymentSum(int $userId, \Carbon\Carbon $since): float
    {
        return (float)(DB::table('balance_transactions')
            ->where('user_id', $userId)
            ->where('created_at', '>=', $since)
            ->sum('amount') ?? 0);
    }

    private function getMaxPaymentAmount(int $userId, \Carbon\Carbon $since): float
    {
        return (float)(DB::table('balance_transactions')
            ->where('user_id', $userId)
            ->where('created_at', '>=', $since)
            ->max('amount') ?? 0);
    }

    private function getMinPaymentAmount(int $userId, \Carbon\Carbon $since): float
    {
        return (float)(DB::table('balance_transactions')
            ->where('user_id', $userId)
            ->where('created_at', '>=', $since)
            ->min('amount') ?? 0);
    }

    private function getDeviceChanges(int $userId, \Carbon\Carbon $since): int
    {
        return DB::table('balance_transactions')
            ->where('user_id', $userId)
            ->where('created_at', '>=', $since)
            ->distinct('device_fingerprint')
            ->count('device_fingerprint');
    }

    private function getIpChanges(int $userId, \Carbon\Carbon $since): int
    {
        return DB::table('balance_transactions')
            ->where('user_id', $userId)
            ->where('created_at', '>=', $since)
            ->distinct('ip_address')
            ->count('ip_address');
    }

    private function isNewDevice(int $userId, string $deviceFingerprint): bool
    {
        return !DB::table('balance_transactions')
            ->where('user_id', $userId)
            ->where('device_fingerprint', $deviceFingerprint)
            ->exists();
    }

    private function isNewIp(int $userId, string $ipAddress): bool
    {
        return !DB::table('balance_transactions')
            ->where('user_id', $userId)
            ->where('ip_address', $ipAddress)
            ->exists();
    }

    private function getDeviceTrustScore(int $userId, string $deviceFingerprint): float
    {
        $successfulPayments = DB::table('balance_transactions')
            ->where('user_id', $userId)
            ->where('device_fingerprint', $deviceFingerprint)
            ->where('status', 'completed')
            ->count();

        $totalPayments = DB::table('balance_transactions')
            ->where('user_id', $userId)
            ->where('device_fingerprint', $deviceFingerprint)
            ->count();

        if ($totalPayments === 0) {
            return 0.5; // Нейтральный скор для новых устройств
        }

        return min(1.0, $successfulPayments / $totalPayments);
    }

    private function getUserCreatedAt(int $userId): \Carbon\Carbon
    {
        return DB::table('users')
            ->where('id', $userId)
            ->value('created_at') ?? now();
    }

    private function calculateAvgDaysBetweenPayments(int $userId): int
    {
        $payments = DB::table('balance_transactions')
            ->where('user_id', $userId)
            ->orderBy('created_at')
            ->pluck('created_at')
            ->toArray();

        if (count($payments) < 2) {
            return 30; // Дефолт для новых пользователей
        }

        $intervals = [];
        for ($i = 1; $i < count($payments); $i++) {
            $intervals[] = strtotime($payments[$i]) - strtotime($payments[$i - 1]);
        }

        return (int)(array_sum($intervals) / count($intervals) / 86400); // Преобразуем в дни
    }
}
