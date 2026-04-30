<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use Psr\Log\LoggerInterface;

use App\Domains\Beauty\DTOs\BeautyFraudDetectionDto;
use App\Domains\Beauty\Events\FraudDetectedEvent;
use App\Octane\Services\SwooleTableService;
use App\Services\AuditService;
use App\Services\Fraud\FraudMLService;
use App\Services\Security\RateLimiterService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Carbon\CarbonImmutable;

final readonly class BeautyFraudDetectionService
{
    private const CACHE_TTL = 3600;

    private const FRAUD_THRESHOLD_BLOCK = 0.85;

    private const FRAUD_THRESHOLD_REVIEW = 0.65;

    private const SUSPICIOUS_ACTIONS_THRESHOLD = 10;

    private const SUSPICIOUS_ACTIONS_WINDOW = 300;

    public function __construct(private readonly EventDispatcher $eventDispatcher,
        private readonly LoggerInterface $logger,
        private readonly FraudMLService $fraudML,
        private readonly AuditService $audit,
        private readonly RateLimiterService $rateLimiter,
        private readonly DatabaseManager $db,
        private readonly LogManager $log,
        private readonly RedisConnection $redis,
        private readonly ?SwooleTableService $swooleTableService = null,) {}

    public function analyze(BeautyFraudDetectionDto $dto): array
    {
        return $this->db->transaction(function () use ($dto) {
            // Check cache first for faster repeated checks
            $cached = $this->getCachedFraudScore($dto->userId);
            if ($cached !== null && ($cached['cached_at'] ?? 0) > time() - 300) {
                return [
                    'success' => true,
                    'fraud_score' => round($cached['score'], 4),
                    'risk_level' => $cached['risk_level'],
                    'action_required' => $this->determineAction($cached['score'], $cached['risk_level']),
                    'flags' => [],
                    'correlation_id' => $dto->correlationId,
                    'cached' => true,
                ];
            }

            $mlScore = $this->getMLScore($dto);
            $ruleScore = $this->applyRuleBasedDetection($dto);
            $behaviorScore = $this->analyzeBehavioralPatterns($dto);

            $finalScore = ($mlScore * 0.4) + ($ruleScore * 0.35) + ($behaviorScore * 0.25);

            $riskLevel = $this->calculateRiskLevel($finalScore);
            $actionRequired = $this->determineAction($finalScore, $riskLevel);

            $result = [
                'success' => true,
                'fraud_score' => round($finalScore, 4),
                'ml_score' => round($mlScore, 4),
                'rule_score' => round($ruleScore, 4),
                'behavior_score' => round($behaviorScore, 4),
                'risk_level' => $riskLevel,
                'action_required' => $actionRequired,
                'flags' => $this->getFlags($dto, $finalScore),
                'correlation_id' => $dto->correlationId,
                'cached' => false,
            ];

            // Cache the result in Swoole Table for faster access
            $this->cacheFraudScore($dto->userId, $finalScore, $riskLevel);

            $this->recordFraudCheck($dto, $result);

            if ($finalScore >= self::FRAUD_THRESHOLD_REVIEW) {
                $this->eventDispatcher->dispatch(new FraudDetectedEvent(
                    userId: $dto->userId,
                    fraudScore: $finalScore,
                    riskLevel: $riskLevel,
                    action: $dto->action,
                    correlationId: $dto->correlationId,
                ));
            }

            $this->log->channel('fraud_alert')->$this->logger->info('Fraud detection analysis completed', [
                'correlation_id' => $dto->correlationId,
                'user_id' => $dto->userId,
                'action' => $dto->action,
                'fraud_score' => $finalScore,
                'risk_level' => $riskLevel,
                'tenant_id' => $dto->tenantId,
            ]);

            $this->audit->record(
                action: 'beauty_fraud_detection',
                subjectType: 'BeautyFraudDetection',
                subjectId: $dto->userId,
                oldValues: [],
                newValues: [
                    'fraud_score' => $finalScore,
                    'risk_level' => $riskLevel,
                    'action' => $dto->action,
                ],
                correlationId: $dto->correlationId,
            );

            return $result;
        });
    }

    public function addSuspiciousIP(string $ip): void
    {
        $key = 'beauty:suspicious_ips';
        $this->redis->sadd($key, $ip);
        $this->redis->expire($key, 86400 * 7);
    }

    public function recordFailedPayment(int $userId): void
    {
        $key = "beauty:failed_payments:{$userId}";
        $this->redis->incr($key);
        $this->redis->expire($key, 86400);
    }

    private function getMLScore(BeautyFraudDetectionDto $dto): float
    {
        try {
            return $this->fraudML->predict([
                'user_id' => $dto->userId,
                'action' => $dto->action,
                'amount' => $dto->amount ?? 0,
                'ip_address' => $dto->ipAddress,
                'user_agent' => $dto->userAgent,
                'tenant_id' => $dto->tenantId,
            ]);
        } catch (\Exception $e) {
            $this->log->channel('fraud_alert')->warning('ML fraud prediction failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $dto->correlationId,
            ]);

            return 0.0;
        }
    }

    private function applyRuleBasedDetection(BeautyFraudDetectionDto $dto): float
    {
        $score = 0.0;

        if ($this->isSuspiciousIP($dto->ipAddress)) {
            $score += 0.3;
        }

        if ($this->hasExcessiveActions($dto->userId)) {
            $score += 0.4;
        }

        if ($this->isUnusualAmount($dto->amount)) {
            $score += 0.2;
        }

        if ($this->isSuspiciousUserAgent($dto->userAgent)) {
            $score += 0.15;
        }

        if ($this->hasRecentFailedPayments($dto->userId)) {
            $score += 0.25;
        }

        return min(1.0, $score);
    }

    private function analyzeBehavioralPatterns(BeautyFraudDetectionDto $dto): float
    {
        $score = 0.0;

        $key = "beauty:user_behavior:{$dto->userId}";
        $behavior = $this->redis->get($key);

        if ($behavior) {
            $data = json_decode($behavior, true);

            if (($data['actions_last_hour'] ?? 0) > 20) {
                $score += 0.3;
            }

            if (($data['unique_masters_last_day'] ?? 0) > 10) {
                $score += 0.2;
            }

            if (($data['cancellation_rate'] ?? 0) > 0.5) {
                $score += 0.4;
            }
        }

        $this->updateBehaviorPattern($dto);

        return min(1.0, $score);
    }

    private function calculateRiskLevel(float $score): string
    {
        if ($score >= self::FRAUD_THRESHOLD_BLOCK) {
            return 'critical';
        }

        if ($score >= self::FRAUD_THRESHOLD_REVIEW) {
            return 'high';
        }

        if ($score >= 0.4) {
            return 'medium';
        }

        return 'low';
    }

    private function determineAction(float $score, string $riskLevel): string
    {
        if ($score >= self::FRAUD_THRESHOLD_BLOCK) {
            return 'block';
        }

        if ($score >= self::FRAUD_THRESHOLD_REVIEW) {
            return 'manual_review';
        }

        if ($riskLevel === 'medium') {
            return 'enhanced_monitoring';
        }

        return 'allow';
    }

    private function getFlags(BeautyFraudDetectionDto $dto, float $score): array
    {
        $flags = [];

        if ($this->isSuspiciousIP($dto->ipAddress)) {
            $flags[] = 'suspicious_ip';
        }

        if ($this->hasExcessiveActions($dto->userId)) {
            $flags[] = 'excessive_actions';
        }

        if ($this->isUnusualAmount($dto->amount)) {
            $flags[] = 'unusual_amount';
        }

        if ($this->hasRecentFailedPayments($dto->userId)) {
            $flags[] = 'recent_failed_payments';
        }

        if ($this->isNewAccount($dto->userId)) {
            $flags[] = 'new_account';
        }

        return $flags;
    }

    private function isSuspiciousIP(?string $ip): bool
    {
        if (! $ip) {
            return false;
        }

        // Try Swoole Table first for faster access
        if ($this->swooleTableService !== null) {
            $fraudCache = $this->swooleTableService->fraudCache();
            if ($fraudCache) {
                $key = 'suspicious_ip:'.md5($ip);
                $cached = $fraudCache->get($key);
                if ($cached && $cached['expires_at'] > time()) {
                    return $cached['risk_score'] > 0;
                }
            }
        }

        // Fallback to Redis
        $key = 'beauty:suspicious_ips';

        return $this->redis->sismember($key, $ip);
    }

    private function hasExcessiveActions(int $userId): bool
    {
        // Try Swoole Table for faster rate limiting
        if ($this->swooleTableService !== null) {
            $rateLimitsTable = $this->swooleTableService->rateLimits();
            if ($rateLimitsTable) {
                $key = "beauty:actions:{$userId}";
                $current = $rateLimitsTable->get($key);
                $count = $current ? $current['count'] + 1 : 1;
                $rateLimitsTable->set($key, [
                    'identifier' => (string) $userId,
                    'key' => 'beauty_actions',
                    'count' => $count,
                    'reset_at' => time() + self::SUSPICIOUS_ACTIONS_WINDOW,
                    'blocked_until' => 0,
                ]);

                return $count > self::SUSPICIOUS_ACTIONS_THRESHOLD;
            }
        }

        // Fallback to Redis
        $key = "beauty:user_actions:{$userId}";

        $count = $this->redis->incr($key);
        $this->redis->expire($key, self::SUSPICIOUS_ACTIONS_WINDOW);

        return $count > self::SUSPICIOUS_ACTIONS_THRESHOLD;
    }

    private function isUnusualAmount(?int $amount): bool
    {
        if (! $amount) {
            return false;
        }

        return $amount > 50000 || $amount < 100;
    }

    private function isSuspiciousUserAgent(?string $userAgent): bool
    {
        if (! $userAgent) {
            return true;
        }

        $suspiciousPatterns = ['/bot/', '/crawler/', '/spider/', '/scraper/'];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, strtolower($userAgent))) {
                return true;
            }
        }

        return false;
    }

    private function hasRecentFailedPayments(int $userId): bool
    {
        $key = "beauty:failed_payments:{$userId}";
        $count = $this->redis->get($key);

        return $count !== null && (int) $count > 3;
    }

    private function isNewAccount(int $userId): bool
    {
        $key = "beauty:user_created:{$userId}";
        $createdAt = $this->redis->get($key);

        if (! $createdAt) {
            return false;
        }

        return (CarbonImmutable::now()->timestamp - (int) $createdAt) < 86400;
    }

    private function updateBehaviorPattern(BeautyFraudDetectionDto $dto): void
    {
        $key = "beauty:user_behavior:{$dto->userId}";
        $behavior = json_decode($this->redis->get($key) ?: '{}', true);

        $behavior['actions_last_hour'] = ($behavior['actions_last_hour'] ?? 0) + 1;
        $behavior['last_action'] = CarbonImmutable::now()->toIso8601String();

        if ($dto->masterId) {
            $mastersKey = "beauty:user_masters:{$dto->userId}";
            $this->redis->sadd($mastersKey, $dto->masterId);
            $this->redis->expire($mastersKey, 86400);
            $behavior['unique_masters_last_day'] = $this->redis->scard($mastersKey);
        }

        $this->redis->setex($key, 86400, json_encode($behavior));
    }

    private function recordFraudCheck(BeautyFraudDetectionDto $dto, array $result): void
    {
        $key = "beauty:fraud_checks:{$dto->userId}";
        $this->redis->lpush($key, json_encode([
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
            'action' => $dto->action,
            'fraud_score' => $result['fraud_score'],
            'risk_level' => $result['risk_level'],
        ]));
        $this->redis->expire($key, 86400 * 30);
    }

    private function cacheFraudScore(int $userId, float $score, string $riskLevel, int $ttl = 3600): void
    {
        if ($this->swooleTableService === null) {
            // Fallback to Redis
            $key = "beauty:fraud_score:{$userId}";
            $this->redis->setex($key, $ttl, json_encode([
                'score' => $score,
                'risk_level' => $riskLevel,
                'cached_at' => time(),
            ]));

            return;
        }

        // Use Swoole Table for faster access
        $fraudCache = $this->swooleTableService->fraudCache();
        if ($fraudCache) {
            $fraudCache->set("beauty:{$userId}", [
                'user_id' => $userId,
                'ip_address' => '',
                'fingerprint' => '',
                'risk_score' => (int) ($score * 100),
                'flags' => $riskLevel,
                'last_checked' => time(),
                'expires_at' => time() + $ttl,
            ]);
        }
    }

    private function getCachedFraudScore(int $userId): ?array
    {
        if ($this->swooleTableService === null) {
            // Fallback to Redis
            $key = "beauty:fraud_score:{$userId}";
            $cached = $this->redis->get($key);

            return $cached ? json_decode($cached, true) : null;
        }

        $fraudCache = $this->swooleTableService->fraudCache();
        if ($fraudCache) {
            $cached = $fraudCache->get("beauty:{$userId}");
            if ($cached && $cached['expires_at'] > time()) {
                return [
                    'score' => $cached['risk_score'] / 100,
                    'risk_level' => $cached['flags'],
                    'cached_at' => $cached['last_checked'],
                ];
            }
        }

        return null;
    }
}
