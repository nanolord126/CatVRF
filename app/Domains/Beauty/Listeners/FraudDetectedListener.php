<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use App\Domains\Beauty\Events\FraudDetectedEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

final class FraudDetectedListener
{
    public function handle(FraudDetectedEvent $event): void
    {
        Log::channel('fraud_alert')->warning('Fraud detected event handled', [
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
        $key = "beauty:fraud_stats:daily:" . now()->toDateString();
        Redis::hincrby($key, 'total_detections', 1);
        Redis::hincrby($key, "risk_{$event->riskLevel}", 1);
        Redis::expire($key, 86400 * 30);
    }

    private function escalateCriticalFraud(FraudDetectedEvent $event): void
    {
        if ($event->riskLevel === 'critical') {
            $key = "beauty:critical_fraud_alerts";
            Redis::lpush($key, json_encode([
                'timestamp' => now()->toIso8601String(),
                'user_id' => $event->userId,
                'fraud_score' => $event->fraudScore,
                'action' => $event->action,
                'correlation_id' => $event->correlationId,
            ]));
            Redis::expire($key, 86400 * 7);
        }
    }
}
