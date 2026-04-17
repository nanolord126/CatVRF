<?php declare(strict_types=1);

namespace App\Services;


use Illuminate\Http\Request;
use App\Services\Fraud\FraudMLService;
use App\Services\Fraud\FraudDataAnonymizer;
use App\Services\Fraud\FraudAtomicLockService;
use App\Services\Fraud\FraudTelemetryService;
use App\Services\Security\RateLimiterService;
use Illuminate\Support\Facades\Cache;


use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

/**
 * Сервис контроля фрода.
 * PRODUCTION MANDATORY — канон CatVRF 2026.
 *
 * Два уровня: hard rules (быстрые) + FraudMLService (ML-scoring).
 * Все подозрительные действия пишутся в fraud_attempts.
 * Логирование ТОЛЬКО в канал fraud_alert (не в audit!).
 */
final readonly class FraudControlService
{
    private const THRESHOLD_BLOCK  = 0.85;
    private const THRESHOLD_REVIEW = 0.65;

    public function __construct(
        private readonly Request $request,
        private FraudMLService $ml,
        private RateLimiterService $rateLimiter,
        private FraudDataAnonymizer $anonymizer,
        private FraudAtomicLockService $atomicLock,
        private FraudTelemetryService $telemetry,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Основная проверка операции на фрод.
     * Выбрасывает FraudBlockedException при score > 0.85.
     */
    public function check(
        int     $userId,
        string  $operationType,
        int     $amount,
        ?string $ipAddress = null,
        ?string $deviceFingerprint = null,
        string  $correlationId = '',
        array   $context = [],
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $score         = 0.0;
        $startTime     = microtime(true);

        // Anonymize context before processing (152-ФЗ compliance)
        $context = $this->anonymizer->anonymizeContext($context, $operationType);

        // 1. Hard rules (быстрый слой)
        $recentOperations = $this->db->table('fraud_attempts')
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->count();

        if ($recentOperations > 5) {
            $score += 0.3;
        }

        if ($amount > 10_000_000 && $this->isNewDevice($userId, $deviceFingerprint)) {
            $score += 0.3;
        }

        if ($this->isNewIp($userId, $ipAddress)) {
            $score += 0.2;
        }

        if ($this->rateLimiter->isSuspicious($userId, $operationType)) {
            $score += 0.4;
        }

        // 2. ML-скоринг
        try {
            $mlResult = $this->ml->scoreOperation(
                userId:            $userId,
                operationType:     $operationType,
                amount:            $amount,
                ipAddress:         $ipAddress ?? $this->request->ip(),
                deviceFingerprint: $deviceFingerprint,
                correlationId:     $correlationId,
                context:           $context,
            );
            $mlScore = (float) ($mlResult['score'] ?? 0.0);
            $score   = max($score, $mlScore);
        } catch (\Throwable $e) {
            // Fallback на hard rules при недоступности ML
            $this->logger->channel('fraud_alert')->warning('FraudMLService unavailable, using hard rules only', [
                'correlation_id' => $correlationId,
                'error'          => $e->getMessage(),
            ]);
        }

        $score    = min($score, 1.0);
        $decision = match (true) {
            $score > self::THRESHOLD_BLOCK  => 'block',
            $score > self::THRESHOLD_REVIEW => 'review',
            default                         => 'allow',
        };

        // 3. Сохраняем в fraud_attempts при любом подозрении
        if ($score > 0.4) {
            $this->db->table('fraud_attempts')->insert([
                'tenant_id'          => function_exists('tenant') && tenant() ? tenant()->id : null,
                'user_id'            => $userId,
                'operation_type'     => $operationType,
                'ip_address'         => $ipAddress ?? $this->request->ip(),
                'device_fingerprint' => $deviceFingerprint
                    ? hash('sha256', $deviceFingerprint)
                    : null,
                'correlation_id'     => $correlationId,
                'ml_score'           => $score,
                'decision'           => $decision,
                'reason'             => $decision === 'block' ? 'High fraud score' : 'Requires review',
                'blocked_at'         => $decision === 'block' ? now() : null,
                'created_at'         => now(),
                'updated_at'         => now(),
                'features_json'      => json_encode($context),
            ]);
        }

        // 4. Invalidate cache tags on block
        if ($decision === 'block') {
            Cache::tags(['fraud', "fraud:user:{$userId}"])->flush();
        }

        // 5. Логируем в fraud_alert (НЕ в audit!)
        $this->logger->channel('fraud_alert')->info('Fraud check completed', [
            'correlation_id' => $correlationId,
            'user_id'        => $userId,
            'operation_type' => $operationType,
            'score'          => $score,
            'decision'       => $decision,
        ]);

        // 6. Record telemetry metrics
        $latencyMs = (microtime(true) - $startTime) * 1000;
        $this->telemetry->recordCheck(
            operationType: $operationType,
            score: $score,
            decision: $decision,
            latencyMs: $latencyMs,
            correlationId: $correlationId,
        );

        if ($decision === 'block') {
            throw new \App\Exceptions\FraudBlockedException(
                "Operation blocked by fraud control. Score: {$score}",
                $correlationId,
            );
        }

        return [
            'score'     => $score,
            'decision'  => $decision,
            'threshold' => self::THRESHOLD_BLOCK,
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
            $this->logger->channel('fraud_alert')->warning('Recommendation abuse detected', [
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
