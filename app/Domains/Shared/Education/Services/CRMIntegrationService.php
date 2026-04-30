<?php

declare(strict_types=1);

namespace App\Domains\Education\Services;

use Psr\Log\LoggerInterface;

use App\Services\Fraud\FraudControlService;

use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Carbon\CarbonImmutable;

final readonly class CRMIntegrationService
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly FraudControlService $fraudControlService,
        private readonly AuditService $audit,
        private readonly DatabaseManager $db,
        private readonly HttpFactory $http,
        private readonly LogManager $log,) {}

    public function syncEnrollmentCreated(int $enrollmentId, array $enrollmentData, string $correlationId): void
    {
        $this->sendToCRM([
            'event' => 'enrollment_created',
            'enrollment_id' => $enrollmentId,
            'user_id' => $enrollmentData['user_id'],
            'course_id' => $enrollmentData['course_id'],
            'mode' => $enrollmentData['mode'],
            'total_price_rub' => $enrollmentData['total_price_rub'],
            'status' => 'active',
            'correlation_id' => $correlationId,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ], $correlationId, 'enrollment_created');
    }

    public function syncProgressUpdated(int $enrollmentId, int $progressPercent, string $correlationId): void
    {
        $this->sendToCRM([
            'event' => 'progress_updated',
            'enrollment_id' => $enrollmentId,
            'progress_percent' => $progressPercent,
            'correlation_id' => $correlationId,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ], $correlationId, 'progress_updated');
    }

    public function syncCertificateIssued(int $enrollmentId, array $certificateData, string $correlationId): void
    {
        $this->sendToCRM([
            'event' => 'certificate_issued',
            'enrollment_id' => $enrollmentId,
            'certificate_id' => $certificateData['certificate_id'],
            'certificate_number' => $certificateData['certificate_number'],
            'issued_at' => $certificateData['issued_at'],
            'valid_until' => $certificateData['valid_until'],
            'correlation_id' => $correlationId,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ], $correlationId, 'certificate_issued');
    }

    public function syncEnrollmentCancelled(int $enrollmentId, string $reason, string $correlationId): void
    {
        $this->sendToCRM([
            'event' => 'enrollment_cancelled',
            'enrollment_id' => $enrollmentId,
            'reason' => $reason,
            'correlation_id' => $correlationId,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ], $correlationId, 'enrollment_cancelled');
    }

    public function syncLiveSessionStarted(string $sessionId, int $slotId, int $teacherId, string $correlationId): void
    {
        $this->sendToCRM([
            'event' => 'live_session_started',
            'session_id' => $sessionId,
            'slot_id' => $slotId,
            'teacher_id' => $teacherId,
            'correlation_id' => $correlationId,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ], $correlationId, 'live_session_started');
    }

    public function syncLiveSessionEnded(string $sessionId, int $participantCount, string $correlationId): void
    {
        $this->sendToCRM([
            'event' => 'live_session_ended',
            'session_id' => $sessionId,
            'participant_count' => $participantCount,
            'correlation_id' => $correlationId,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ], $correlationId, 'live_session_ended');
    }

    public function syncPaymentCaptured(int $milestoneId, int $amountKopecks, string $correlationId): void
    {
        $this->sendToCRM([
            'event' => 'payment_captured',
            'milestone_id' => $milestoneId,
            'amount_rub' => $amountKopecks / 100,
            'correlation_id' => $correlationId,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ], $correlationId, 'payment_captured');
    }

    public function syncTeacherPayoutReleased(int $payoutId, int $teacherId, int $amountKopecks, string $correlationId): void
    {
        $this->sendToCRM([
            'event' => 'teacher_payout_released',
            'payout_id' => $payoutId,
            'teacher_id' => $teacherId,
            'amount_rub' => $amountKopecks / 100,
            'correlation_id' => $correlationId,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ], $correlationId, 'teacher_payout_released');
    }

    public function processRetries(): array
    {
        $this->fraudControlService->check('process', ['context' => __CLASS__]);
        $failedSyncs = $this->db->table('crm_sync_queue')
            ->where('next_retry_at', '<=', CarbonImmutable::now())
            ->where('retry_count', '<', 5)
            ->limit(50)
            ->get();

        $processed = [];

        foreach ($failedSyncs as $sync) {
            try {
                $this->db->table('crm_sync_queue')->where('id', $sync->id)->delete();
                $processed[] = ['id' => $sync->id, 'status' => 'retried'];
            } catch (\Exception $e) {
                $this->db->table('crm_sync_queue')
                    ->where('id', $sync->id)
                    ->update([
                        'retry_count' => $sync->retry_count + 1,
                        'next_retry_at' => CarbonImmutable::now()->addMinutes(10 * ($sync->retry_count + 1)),
                    ]);
            }
        }

        return [
            'processed_count' => count($processed),
            'items' => $processed,
        ];
    }

    private function sendToCRM(array $data, string $correlationId, string $eventType): void
    {
        $webhookUrl = config('services.crm.webhook_url');

        if ($webhookUrl === null) {
            $this->log->channel('audit')->warning('CRM webhook URL not configured', [
                'correlation_id' => $correlationId,
                'event_type' => $eventType,
            ]);

            return;
        }

        try {
            $response = $this->http->timeout(10)
                ->withHeaders([
                    'X-Correlation-ID' => $correlationId,
                    'Content-Type' => 'application/json',
                ])
                ->post($webhookUrl, $data);

            if ($response->successful()) {
                $this->audit->record('crm_sync_success', 'CRMIntegration', null, [], [
                    'correlation_id' => $correlationId,
                    'event_type' => $eventType,
                    'crm_response' => $response->body(),
                ], $correlationId);

                $this->log->channel('audit')->$this->logger->info('CRM sync successful', [
                    'correlation_id' => $correlationId,
                    'event_type' => $eventType,
                ]);
            } else {
                $this->handleCRMError($correlationId, $eventType, $response->status(), $response->body());
            }
        } catch (\Exception $e) {
            $this->handleCRMError($correlationId, $eventType, 0, $e->getMessage());
        }
    }

    private function handleCRMError(string $correlationId, string $eventType, int $statusCode, string $errorMessage): void
    {
        $this->audit->record('crm_sync_failed', 'CRMIntegration', null, [], [
            'correlation_id' => $correlationId,
            'event_type' => $eventType,
            'status_code' => $statusCode,
            'error_message' => $errorMessage,
        ], $correlationId);

        $this->log->channel('audit')->error('CRM sync failed', [
            'correlation_id' => $correlationId,
            'event_type' => $eventType,
            'status_code' => $statusCode,
            'error' => $errorMessage,
        ]);

        $this->queueRetry($correlationId, $eventType, $statusCode, $errorMessage);
    }

    private function queueRetry(string $correlationId, string $eventType, int $statusCode, string $errorMessage): void
    {
        $this->db->table('crm_sync_queue')->insert([
            'id' => (string) Str::uuid(),
            'correlation_id' => $correlationId,
            'event_type' => $eventType,
            'status_code' => $statusCode,
            'error_message' => $errorMessage,
            'retry_count' => 0,
            'next_retry_at' => CarbonImmutable::now()->addMinutes(5),
            'created_at' => CarbonImmutable::now(),
        ]);
    }
}
