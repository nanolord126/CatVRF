<?php declare(strict_types=1);

namespace App\Domains\Education\Listeners;

use App\Domains\Education\Events\FraudDetectedEvent;
use App\Services\AuditService;
use Illuminate\Support\Facades\Log;

final readonly class FraudDetectedListener
{
    public function __construct(
        private AuditService $audit,
    ) {}

    public function handle(FraudDetectedEvent $event): void
    {
        $this->audit->record('education_fraud_detected_crm_sync', 'FraudDetectedEvent', null, [], [
            'correlation_id' => $event->correlationId,
            'tenant_id' => $event->tenantId,
            'fraud_id' => $event->fraudId,
            'fraud_type' => $event->fraudType,
            'severity' => $event->severity,
            'user_id' => $event->userId,
            'enrollment_id' => $event->enrollmentId,
            'review_id' => $event->reviewId,
        ], $event->correlationId);

        Log::channel('audit')->warning('Fraud detected synced to CRM', [
            'correlation_id' => $event->correlationId,
            'fraud_id' => $event->fraudId,
            'fraud_type' => $event->fraudType,
        ]);

        $this->sendToCRM($event);
        $this->sendAlert($event);
    }

    private function sendToCRM(FraudDetectedEvent $event): void
    {
        $crmData = [
            'event' => 'fraud_detected',
            'fraud_id' => $event->fraudId,
            'fraud_type' => $event->fraudType,
            'severity' => $event->severity,
            'user_id' => $event->userId,
            'enrollment_id' => $event->enrollmentId,
            'review_id' => $event->reviewId,
            'tenant_id' => $event->tenantId,
            'correlation_id' => $event->correlationId,
            'timestamp' => now()->toIso8601String(),
        ];

        $webhookUrl = config('services.crm.webhook_url');

        if ($webhookUrl !== null) {
            try {
                \Illuminate\Support\Facades\Http::timeout(10)->post($webhookUrl, $crmData);
            } catch (\Exception $e) {
                Log::channel('audit')->error('CRM fraud sync failed', [
                    'correlation_id' => $event->correlationId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function sendAlert(FraudDetectedEvent $event): void
    {
        if ($event->severity === 'critical' || $event->severity === 'high') {
            Log::channel('audit')->critical('Critical fraud alert', [
                'correlation_id' => $event->correlationId,
                'fraud_id' => $event->fraudId,
                'fraud_type' => $event->fraudType,
                'severity' => $event->severity,
                'user_id' => $event->userId,
            ]);
        }
    }
}
