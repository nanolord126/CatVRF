<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Education;

use Tests\TestCase;
use App\Domains\Education\Services\CRMIntegrationService;
use App\Services\AuditService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Mockery;

final class CRMIntegrationServiceTest extends TestCase
{
    private CRMIntegrationService $service;
    private AuditService $audit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->audit = Mockery::mock(AuditService::class);

        $this->service = new CRMIntegrationService($this->audit);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_sync_enrollment_created_success(): void
    {
        config(['services.crm.webhook_url' => 'https://crm.example.com/webhook']);

        Http::fake([
            'https://crm.example.com/webhook' => Http::response(['success' => true], 200),
        ]);

        $this->audit->shouldReceive('record')->once();

        $this->service->syncEnrollmentCreated(1, [
            'user_id' => 1,
            'course_id' => 1,
            'mode' => 'b2c',
            'total_price_rub' => 1000,
        ], 'test-correlation');
    }

    public function test_sync_enrollment_created_no_webhook(): void
    {
        config(['services.crm.webhook_url' => null]);

        $this->audit->shouldReceive('record')->once();

        $this->service->syncEnrollmentCreated(1, [
            'user_id' => 1,
            'course_id' => 1,
            'mode' => 'b2c',
            'total_price_rub' => 1000,
        ], 'test-correlation');
    }

    public function test_sync_progress_updated(): void
    {
        config(['services.crm.webhook_url' => 'https://crm.example.com/webhook']);

        Http::fake([
            'https://crm.example.com/webhook' => Http::response(['success' => true], 200),
        ]);

        $this->audit->shouldReceive('record')->once();

        $this->service->syncProgressUpdated(1, 75, 'test-correlation');
    }

    public function test_sync_certificate_issued(): void
    {
        config(['services.crm.webhook_url' => 'https://crm.example.com/webhook']);

        Http::fake([
            'https://crm.example.com/webhook' => Http::response(['success' => true], 200),
        ]);

        $this->audit->shouldReceive('record')->once();

        $this->service->syncCertificateIssued(1, [
            'certificate_id' => 'cert-123',
            'certificate_number' => 'CERT-ABC123-20260416',
            'issued_at' => now()->toIso8601String(),
            'valid_until' => now()->addYears(2)->toIso8601String(),
        ], 'test-correlation');
    }

    public function test_sync_enrollment_cancelled(): void
    {
        config(['services.crm.webhook_url' => 'https://crm.example.com/webhook']);

        Http::fake([
            'https://crm.example.com/webhook' => Http::response(['success' => true], 200),
        ]);

        $this->audit->shouldReceive('record')->once();

        $this->service->syncEnrollmentCancelled(1, 'User request', 'test-correlation');
    }

    public function test_sync_live_session_started(): void
    {
        config(['services.crm.webhook_url' => 'https://crm.example.com/webhook']);

        Http::fake([
            'https://crm.example.com/webhook' => Http::response(['success' => true], 200),
        ]);

        $this->audit->shouldReceive('record')->once();

        $this->service->syncLiveSessionStarted('session-1', 1, 1, 'test-correlation');
    }

    public function test_sync_live_session_ended(): void
    {
        config(['services.crm.webhook_url' => 'https://crm.example.com/webhook']);

        Http::fake([
            'https://crm.example.com/webhook' => Http::response(['success' => true], 200),
        ]);

        $this->audit->shouldReceive('record')->once();

        $this->service->syncLiveSessionEnded('session-1', 10, 'test-correlation');
    }

    public function test_sync_payment_captured(): void
    {
        config(['services.crm.webhook_url' => 'https://crm.example.com/webhook']);

        Http::fake([
            'https://crm.example.com/webhook' => Http::response(['success' => true], 200),
        ]);

        $this->audit->shouldReceive('record')->once();

        $this->service->syncPaymentCaptured(1, 100000, 'test-correlation');
    }

    public function test_sync_teacher_payout_released(): void
    {
        config(['services.crm.webhook_url' => 'https://crm.example.com/webhook']);

        Http::fake([
            'https://crm.example.com/webhook' => Http::response(['success' => true], 200),
        ]);

        $this->audit->shouldReceive('record')->once();

        $this->service->syncTeacherPayoutReleased(1, 1, 85000, 'test-correlation');
    }

    public function test_process_retries(): void
    {
        DB::table('crm_sync_queue')->insert([
            'id' => 'retry-1',
            'correlation_id' => 'test-correlation',
            'event_type' => 'enrollment_created',
            'status_code' => 500,
            'error_message' => 'Server error',
            'retry_count' => 0,
            'next_retry_at' => now()->subMinute(),
        ]);

        $result = $this->service->processRetries();

        $this->assertEquals(1, $result['processed_count']);
    }
}
