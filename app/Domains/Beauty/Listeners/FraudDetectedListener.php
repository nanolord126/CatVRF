<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use App\Domains\Beauty\Events\FraudDetectedEvent;
use Illuminate\Log\LogManager;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Carbon\CarbonImmutable;

final class FraudDetectedListener
{
    public function __construct(
        private readonly LogManager $log,
        private readonly RedisConnection $redis,
    ) {}
    public function handle(FraudDetectedEvent $event): void
    {
        $this->log->channel('fraud_alert')->warning('Fraud detected event handled', [
            'correlation_id' => $event->correlationId,
            'user_id' => $event->userId,
            'fraud_score' => $event->fraudScore,
            'risk_level' => $event->riskLevel,
            'action' => $event->action,
        ]);

        $this->trackFraudStatistics($event);
        $this->escalateCriticalFraud($event);
    }

    private function trackFraudStatistics(FraudDetectedEvent $event): void
    {
        $key = 'beauty:fraud_stats:daily:'.CarbonImmutable::now()->toDateString();
        $this->redis->hincrby($key, 'total_detections', 1);
        $this->redis->hincrby($key, "risk_{$event->riskLevel}", 1);
        $this->redis->expire($key, 86400 * 30);
    }

    private function escalateCriticalFraud(FraudDetectedEvent $event): void
    {
        if ($event->riskLevel === 'critical') {
            $key = 'beauty:critical_fraud_alerts';
            $this->redis->lpush($key, json_encode([
                'timestamp' => CarbonImmutable::now()->toIso8601String(),
                'user_id' => $event->userId,
                'fraud_score' => $event->fraudScore,
                'action' => $event->action,
                'correlation_id' => $event->correlationId,
            ]));
            $this->redis->expire($key, 86400 * 7);
        }
    }
}
