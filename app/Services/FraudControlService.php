<?php declare(strict_types=1);

namespace App\Services;

use App\Models\FraudAttempt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FraudControlService
{
    private const THRESHOLD = 0.7;

    public function check(
        int $userId,
        string $operationType,
        int $amount,
        ?string $ipAddress = null,
        ?string $deviceFingerprint = null,
        string $correlationId = '',
    ): array {
        $score = $this->calculateScore([
            'user_id' => $userId,
            'operation_type' => $operationType,
            'amount' => $amount,
            'ip_address' => $ipAddress,
            'device_fingerprint' => $deviceFingerprint,
        ]);

        $decision = $score > self::THRESHOLD ? 'block' : 'allow';

        $this->log->channel('audit')->info('Fraud check', [
            'correlation_id' => $correlationId ?: Str::uuid()->toString(),
            'user_id' => $userId,
            'operation_type' => $operationType,
            'score' => $score,
            'decision' => $decision,
        ]);

        return [
            'score' => $score,
            'decision' => $decision,
            'threshold' => self::THRESHOLD,
        ];
    }

    public function checkRecommendation(int $userId, int|string $tenantId = 0, string $correlationId = ''): bool
    {
        // Проверка на накрутку рекомендаций
        $recentClicks = $this->db->table('recommendation_logs')
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->count();

        if ($recentClicks > 50) {
            $this->log->channel('audit')->warning('Recommendation abuse detected', [
                'correlation_id' => $correlationId ?: (string) \Illuminate\Support\Str::uuid(),
                'user_id' => $userId,
                'recent_clicks' => $recentClicks,
            ]);

            return false;
        }

        return true;
    }

    private function calculateScore(array $data): float
    {
        $score = 0.0;

        // Проверка количества операций за 5 минут
        $recentOperations = $this->db->table('fraud_attempts')
            ->where('user_id', $data['user_id'])
            ->where('created_at', '>=', now()->subMinutes(5))
            ->count();

        if ($recentOperations > 5) {
            $score += 0.3;
        }

        // Проверка большой суммы с нового устройства
        if ($data['amount'] > 10000000 && $this->isNewDevice($data['user_id'], $data['device_fingerprint'])) {
            $score += 0.3;
        }

        // Проверка смены IP
        if ($this->isNewIp($data['user_id'], $data['ip_address'])) {
            $score += 0.2;
        }

        // ML-скоринг (если доступен)
        if (class_exists('App\Services\FraudMLService')) {
            $mlScore = app('App\Services\FraudMLService')->scoreOperation($data);
            $score = max($score, $mlScore);
        }

        return min($score, 1.0);
    }

    private function isNewDevice(int $userId, ?string $deviceFingerprint): bool
    {
        if (!$deviceFingerprint) {
            return false;
        }

        $exists = $this->db->table('fraud_attempts')
            ->where('user_id', $userId)
            ->where('device_fingerprint', $deviceFingerprint)
            ->where('created_at', '>=', now()->subDays(30))
            ->exists();

        return !$exists;
    }

    private function isNewIp(int $userId, ?string $ipAddress): bool
    {
        if (!$ipAddress) {
            return false;
        }

        $exists = $this->db->table('fraud_attempts')
            ->where('user_id', $userId)
            ->where('ip_address', $ipAddress)
            ->where('created_at', '>=', now()->subDays(30))
            ->exists();

        return !$exists;
    }
}
