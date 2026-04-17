<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Beauty\Services;

use App\Domains\Beauty\DTOs\BeautyFraudDetectionDto;
use App\Domains\Beauty\Services\BeautyFraudDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BeautyFraudDetectionServiceTest extends TestCase
{
    use RefreshDatabase;

    private BeautyFraudDetectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BeautyFraudDetectionService::class);
    }

    public function test_analyze_fraud(): void
    {
        $dto = new BeautyFraudDetectionDto(
            tenantId: 1,
            businessGroupId: null,
            userId: 1,
            action: 'appointment_booking',
            appointmentId: null,
            masterId: null,
            amount: 1000,
            ipAddress: '192.168.1.1',
            userAgent: 'Mozilla/5.0',
            correlationId: 'test-correlation',
        );

        $result = $this->service->analyze($dto);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('fraud_score', $result);
        $this->assertArrayHasKey('risk_level', $result);
        $this->assertArrayHasKey('action_required', $result);
        $this->assertTrue($result['success']);
    }

    public function test_risk_level_classification(): void
    {
        $dto = new BeautyFraudDetectionDto(
            tenantId: 1,
            businessGroupId: null,
            userId: 1,
            action: 'appointment_booking',
            correlationId: 'test-correlation',
        );

        $result = $this->service->analyze($dto);

        $this->assertContains($result['risk_level'], ['low', 'medium', 'high', 'critical']);
    }

    public function test_add_suspicious_ip(): void
    {
        $this->service->addSuspiciousIP('192.168.1.100');

        $this->assertTrue(true);
    }

    public function test_record_failed_payment(): void
    {
        $this->service->recordFailedPayment(1);

        $this->assertTrue(true);
    }

    public function test_fraud_score_between_0_and_1(): void
    {
        $dto = new BeautyFraudDetectionDto(
            tenantId: 1,
            businessGroupId: null,
            userId: 1,
            action: 'appointment_booking',
            correlationId: 'test-correlation',
        );

        $result = $this->service->analyze($dto);

        $this->assertGreaterThanOrEqual(0, $result['fraud_score']);
        $this->assertLessThanOrEqual(1, $result['fraud_score']);
    }
}
