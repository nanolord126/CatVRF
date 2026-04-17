<?php declare(strict_types=1);

namespace App\Domains\Education\Services;

use App\Services\AuditService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

final readonly class CRMIntegrationService
{
    public function __construct(
        private AuditService $audit,
    ) {}

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
            'timestamp' => now()->toIso8601String(),
        ], $correlationId, 'enrollment_created');
    }

    public function syncProgressUpdated(int $enrollmentId, int $progressPercent, string $correlationId): void
    {
        $this->sendToCRM([
            'event' => 'progress_updated',
            'enrollment_id' => $enrollmentId,
            'progress_percent' => $progressPercent,
            'correlation_id' => $correlationId,
            'timestamp' => now()->toIso8601String(),
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
            'timestamp' => now()->toIso8601String(),
        ], $correlationId, 'certificate_issued');
    }

    public function syncEnrollmentCancelled(int $enrollmentId, string $reason, string $correlationId): void
    {
        $this->sendToCRM([
            'event' => 'enrollment_cancelled',
            'enrollment_id' => $enrollmentId,
            'reason' => $reason,
            'correlation_id' => $correlationId,
            'timestamp' => now()->toIso8601String(),
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
            'timestamp' => now()->toIso8601String(),
        ], $correlationId, 'live_session_started');
    }

    public function syncLiveSessionEnded(string $sessionId, int $participantCount, string $correlationId): void
    {
        $this->sendToCRM([
            'event' => 'live_session_ended',
            'session_id' => $sessionId,
            'participant_count' => $participantCount,
            'correlation_id' => $correlationId,
            'timestamp' => now()->toIso8601String(),
        ], $correlationId, 'live_session_ended');
    }

    public function syncPaymentCaptured(int $milestoneId, int $amountKopecks, string $correlationId): void
    {
        $this->sendToCRM([
            'event' => 'payment_captured',
            'milestone_id' => $milestoneId,
            'amount_rub' => $amountKopecks / 100,
            'correlation_id' => $correlationId,
            'timestamp' => now()->toIso8601String(),
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
            'timestamp' => now()->toIso8601String(),
        ], $correlationId, 'teacher_payout_released');
    }

    private function sendToCRM(array $data, string $correlationId, string $eventType): void
    {
        $webhookUrl = config('services.crm.webhook_url');

        if ($webhookUrl === null) {
            Log::channel('audit')->warning('CRM webhook URL not configured', [
                'correlation_id' => $correlationId,
                'event_type' => $eventType,
            ]);
            return;
        }

        try {
            $response = Http::timeout(10)
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

                Log::channel('audit')->info('CRM sync successful', [
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

        Log::channel('audit')->error('CRM sync failed', [
            'correlation_id' => $correlationId,
            'event_type' => $eventType,
            'status_code' => $statusCode,
            'error' => $errorMessage,
        ]);

        $this->queueRetry($correlationId, $eventType, $statusCode, $errorMessage);
    }

    private function queueRetry(string $correlationId, string $eventType, int $statusCode, string $errorMessage): void
    {
        DB::table('crm_sync_queue')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'correlation_id' => $correlationId,
            'event_type' => $eventType,
            'status_code' => $statusCode,
            'error_message' => $errorMessage,
            'retry_count' => 0,
            'next_retry_at' => now()->addMinutes(5),
            'created_at' => now(),
        ]);
    }

    public function processRetries(): array
    {
        $failedSyncs = DB::table('crm_sync_queue')
            ->where('next_retry_at', '<=', now())
            ->where('retry_count', '<', 5)
            ->limit(50)
            ->get();

        $processed = [];

        foreach ($failedSyncs as $sync) {
            try {
                DB::table('crm_sync_queue')->where('id', $sync->id)->delete();
                $processed[] = ['id' => $sync->id, 'status' => 'retried'];
            } catch (\Exception $e) {
                DB::table('crm_sync_queue')
                    ->where('id', $sync->id)
                    ->update([
                        'retry_count' => $sync->retry_count + 1,
                        'next_retry_at' => now()->addMinutes(10 * ($sync->retry_count + 1)),
                    ]);
            }
        }

        return [
            'processed_count' => count($processed),
            'items' => $processed,
        ];
    }
}
