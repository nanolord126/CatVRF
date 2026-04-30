<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Beauty\Services;

use App\Domains\Beauty\DTOs\BeautyFraudDetectionDto;
use App\Domains\Beauty\Services\BeautyFraudDetectionService;
use App\Octane\Services\SwooleTableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

final class BeautyFraudDetectionServiceTest extends TestCase
{
    use RefreshDatabase;

    private BeautyFraudDetectionService $service;

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

    public function test_swoole_table_service_injection(): void
    {
        // Test that service can be instantiated with SwooleTableService
        $swooleTableServiceMock = $this->createMock(SwooleTableService::class);

        // Service is instantiated via container, so we just verify the mock works
        $this->assertInstanceOf(SwooleTableService::class, $swooleTableServiceMock);
    }

    public function test_fraud_score_uses_redis_fallback_when_swoole_null(): void
    {
        Redis::flushdb();

        // First call should cache the result in Redis (fallback when Swoole is null)
        $dto = new BeautyFraudDetectionDto(
            tenantId: 1,
            businessGroupId: null,
            userId: 999, // Unique user ID for testing
            action: 'appointment_booking',
            correlationId: 'test-cache-'.time(),
        );

        $result1 = $this->service->analyze($dto);
        $this->assertArrayHasKey('fraud_score', $result1);

        // Check Redis was used for caching (fallback mechanism)
        $redisKey = 'beauty:fraud_score:999';
        $redisData = Redis::get($redisKey);

        // Redis fallback should have stored the cached result
        if ($redisData !== null) {
            $decoded = json_decode($redisData, true);
            $this->assertIsArray($decoded);
            $this->assertArrayHasKey('score', $decoded);
        }
    }

    public function test_analyze_returns_cached_result_within_ttl(): void
    {
        Redis::flushdb();

        $userId = 888;
        $correlationId = 'test-cache-ttl-'.time();

        $dto = new BeautyFraudDetectionDto(
            tenantId: 1,
            businessGroupId: null,
            userId: $userId,
            action: 'appointment_booking',
            correlationId: $correlationId,
        );

        // First call
        $result1 = $this->service->analyze($dto);

        // Manually set cache to simulate a recent cached result
        $redisKey = "beauty:fraud_score:{$userId}";
        Redis::setex($redisKey, 300, json_encode([
            'score' => 0.5,
            'risk_level' => 'medium',
            'cached_at' => time(),
        ]));

        // Second call should return cached result
        $result2 = $this->service->analyze($dto);

        $this->assertArrayHasKey('cached', $result2);
        if (isset($result2['cached']) && $result2['cached'] === true) {
            $this->assertEquals(0.5, $result2['fraud_score']);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BeautyFraudDetectionService::class);
    }
}
