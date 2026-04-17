<?php

declare(strict_types=1);

namespace App\Domains\Sports\Listeners;

use App\Domains\Sports\Events\FraudDetectedEvent;
use App\Services\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleFraudDetectedListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private AuditService $audit,
    ) {}

    public function handle(FraudDetectedEvent $event): void
    {
        Log::channel('security')->critical('Fraud detected in Sports vertical', [
            'user_id' => $event->userId,
            'fraud_type' => $event->fraudType,
            'risk_score' => $event->riskScore,
            'correlation_id' => $event->correlationId,
        ]);

        $this->audit->record(
            'sports_fraud_detected_handled',
            'sports_fraud',
            $event->userId,
            [],
            [
                'fraud_type' => $event->fraudType,
                'risk_score' => $event->riskScore,
                'fraud_details' => $event->fraudDetails,
                'correlation_id' => $event->correlationId,
            ],
            $event->correlationId
        );

        if ($event->riskScore >= 0.75) {
            $this->triggerHighRiskAlert($event);
        }
    }

    private function triggerHighRiskAlert(FraudDetectedEvent $event): void
    {
        Log::channel('security')->critical('HIGH RISK FRAUD - Immediate action required', [
            'user_id' => $event->userId,
            'fraud_type' => $event->fraudType,
            'risk_score' => $event->riskScore,
            'correlation_id' => $event->correlationId,
        ]);
    }

    public function failed(FraudDetectedEvent $event, \Throwable $exception): void
    {
        Log::channel('security')->error('Failed to handle fraud detection event', [
            'user_id' => $event->userId,
            'fraud_type' => $event->fraudType,
            'error' => $exception->getMessage(),
            'correlation_id' => $event->correlationId,
        ]);
    }
}
