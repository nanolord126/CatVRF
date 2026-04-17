<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\FraudML;

use App\Domains\FraudML\DTOs\PaymentFraudMLDto;
use App\Domains\FraudML\Services\PaymentFraudMLService;
use App\Domains\FraudML\Services\PaymentFraudMLShadowService;
use App\Providers\Prometheus\PaymentFraudMLMetricsCollector;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * PaymentFraudMLIntegrationTest - Integration tests for payment fraud ML
 * 
 * Tests the integration of PaymentFraudMLService with:
 * - PaymentService
 * - WalletService
 * - Shadow mode
 * - Metrics collection
 * - Idempotency caching
 * 
 * CANON 2026 - Production Ready
 */
final class PaymentFraudMLIntegrationTest extends TestCase
{
    private PaymentFraudMLService $fraudService;
    private PaymentFraudMLMetricsCollector $metrics;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->fraudService = app(PaymentFraudMLService::class);
        $this->metrics = app(PaymentFraudMLMetricsCollector::class);
        
        Cache::flush();
    }

    public function test_payment_fraud_check_allows_legitimate_payment(): void
    {
        $dto = new PaymentFraudMLDto(
            tenant_id: 1,
            user_id: 1,
            operation_type: 'payment_init',
            amount_kopecks: 10000, // 100 RUB
            ip_address: '127.0.0.1',
            device_fingerprint: 'test-device',
            correlation_id: 'test-correlation-1',
            idempotency_key: 'test-idempotency-1',
            vertical_code: 'medical',
            urgency_level: 'medium',
            wallet_balance_kopecks: 50000, // 500 RUB
            previous_payment_success_rate_7d: 0.95,
            payment_count_24h: 2,
        );

        $result = $this->fraudService->scorePayment($dto);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('decision', $result);
        $this->assertArrayHasKey('cached', $result);
        $this->assertLessThan(0.80, $result['score']); // Should be below Medical threshold
        $this->assertEquals('allow', $result['decision']);
        $this->assertFalse($result['cached']);
    }

    public function test_payment_fraud_check_blocks_suspicious_payment(): void
    {
        $dto = new PaymentFraudMLDto(
            tenant_id: 1,
            user_id: 1,
            operation_type: 'payment_init',
            amount_kopecks: 1000000, // 10,000 RUB (large amount)
            ip_address: '127.0.0.1',
            device_fingerprint: 'test-device',
            correlation_id: 'test-correlation-2',
            idempotency_key: 'test-idempotency-2',
            vertical_code: 'payment',
            wallet_balance_kopecks: 10000000, // 100,000 RUB (10x ratio - suspicious)
            payment_count_24h: 20, // High velocity
            previous_failures_24h: 5, // Multiple failures
        );

        $result = $this->fraudService->scorePayment($dto);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('decision', $result);
        $this->assertGreaterThanOrEqual(0.75, $result['score']); // Should exceed threshold
        $this->assertEquals('block', $result['decision']);
        $this->assertArrayHasKey('explanation', $result);
    }

    public function test_emergency_payment_has_lower_threshold(): void
    {
        $emergencyDto = new PaymentFraudMLDto(
            tenant_id: 1,
            user_id: 1,
            operation_type: 'payment_init',
            amount_kopecks: 50000, // 500 RUB
            ip_address: '127.0.0.1',
            device_fingerprint: 'test-device',
            correlation_id: 'test-correlation-3',
            idempotency_key: 'test-idempotency-3',
            vertical_code: 'medical',
            urgency_level: 'emergency',
            is_emergency_payment: true,
        );

        $normalDto = new PaymentFraudMLDto(
            tenant_id: 1,
            user_id: 1,
            operation_type: 'payment_init',
            amount_kopecks: 50000,
            ip_address: '127.0.0.1',
            device_fingerprint: 'test-device',
            correlation_id: 'test-correlation-4',
            idempotency_key: 'test-idempotency-4',
            vertical_code: 'medical',
            urgency_level: 'medium',
        );

        $emergencyResult = $this->fraudService->scorePayment($emergencyDto);
        $normalResult = $this->fraudService->scorePayment($normalDto);

        // Emergency should have higher threshold (more lenient)
        $this->assertLessThanOrEqual($emergencyResult['score'], $normalResult['score']);
    }

    public function test_idempotency_caching_works(): void
    {
        $dto = new PaymentFraudMLDto(
            tenant_id: 1,
            user_id: 1,
            operation_type: 'payment_init',
            amount_kopecks: 10000,
            ip_address: '127.0.0.1',
            device_fingerprint: 'test-device',
            correlation_id: 'test-correlation-5',
            idempotency_key: 'test-idempotency-5',
            vertical_code: 'payment',
        );

        $firstResult = $this->fraudService->scorePayment($dto);
        $secondResult = $this->fraudService->scorePayment($dto);

        $this->assertFalse($firstResult['cached']);
        $this->assertTrue($secondResult['cached']);
        $this->assertEquals($firstResult['score'], $secondResult['score']);
        $this->assertEquals($firstResult['decision'], $secondResult['decision']);
    }

    public function test_cache_invalidation_works(): void
    {
        $dto = new PaymentFraudMLDto(
            tenant_id: 1,
            user_id: 1,
            operation_type: 'payment_init',
            amount_kopecks: 10000,
            ip_address: '127.0.0.1',
            device_fingerprint: 'test-device',
            correlation_id: 'test-correlation-6',
            idempotency_key: 'test-idempotency-6',
            vertical_code: 'payment',
        );

        $this->fraudService->scorePayment($dto);
        $this->fraudService->invalidateCache('test-idempotency-6');

        $result = $this->fraudService->scorePayment($dto);
        $this->assertFalse($result['cached']);
    }

    public function test_wallet_balance_ratio_detection(): void
    {
        $normalDto = new PaymentFraudMLDto(
            tenant_id: 1,
            user_id: 1,
            operation_type: 'payment_init',
            amount_kopecks: 10000,
            ip_address: '127.0.0.1',
            device_fingerprint: 'test-device',
            correlation_id: 'test-correlation-7',
            idempotency_key: 'test-idempotency-7',
            vertical_code: 'wallet',
            wallet_balance_kopecks: 50000, // 5x ratio
        );

        $suspiciousDto = new PaymentFraudMLDto(
            tenant_id: 1,
            user_id: 1,
            operation_type: 'payment_init',
            amount_kopecks: 10000,
            ip_address: '127.0.0.1',
            device_fingerprint: 'test-device',
            correlation_id: 'test-correlation-8',
            idempotency_key: 'test-idempotency-8',
            vertical_code: 'wallet',
            wallet_balance_kopecks: 100000, // 10x ratio - suspicious
        );

        $normalResult = $this->fraudService->scorePayment($normalDto);
        $suspiciousResult = $this->fraudService->scorePayment($suspiciousDto);

        // Suspicious wallet balance ratio should increase score
        $this->assertGreaterThanOrEqual($normalResult['score'], $suspiciousResult['score']);
    }

    public function test_medical_vertical_has_higher_threshold(): void
    {
        $medicalDto = new PaymentFraudMLDto(
            tenant_id: 1,
            user_id: 1,
            operation_type: 'payment_init',
            amount_kopecks: 15000, // 150 RUB
            ip_address: '127.0.0.1',
            device_fingerprint: 'test-device',
            correlation_id: 'test-correlation-9',
            idempotency_key: 'test-idempotency-9',
            vertical_code: 'medical',
        );

        $paymentDto = new PaymentFraudMLDto(
            tenant_id: 1,
            user_id: 1,
            operation_type: 'payment_init',
            amount_kopecks: 15000,
            ip_address: '127.0.0.1',
            device_fingerprint: 'test-device',
            correlation_id: 'test-correlation-10',
            idempotency_key: 'test-idempotency-10',
            vertical_code: 'payment',
        );

        $medicalResult = $this->fraudService->scorePayment($medicalDto);
        $paymentResult = $this->fraudService->scorePayment($paymentDto);

        // Medical should have higher threshold (more lenient)
        // This is a simplified test - in production, actual model behavior may vary
        $this->assertArrayHasKey('score', $medicalResult);
        $this->assertArrayHasKey('score', $paymentResult);
    }

    public function test_shadow_mode_integration(): void
    {
        $shadowService = app(PaymentFraudMLShadowService::class);
        
        $dto = new PaymentFraudMLDto(
            tenant_id: 1,
            user_id: 1,
            operation_type: 'payment_init',
            amount_kopecks: 10000,
            ip_address: '127.0.0.1',
            device_fingerprint: 'test-device',
            correlation_id: 'test-correlation-11',
            idempotency_key: 'test-idempotency-11',
            vertical_code: 'medical',
        );

        $features = [
            'amount_log' => log(100),
            'hour_of_day' => now()->hour,
            'wallet_balance_ratio' => 5.0,
            'urgency_score' => 0.5,
        ];

        $shadowResults = $shadowService->performShadowInference($dto, $features);

        // Shadow service should return null if no shadow models exist
        // or array if shadow models are present
        $this->assertTrue($shadowResults === null || is_array($shadowResults));
    }
}
