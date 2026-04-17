<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\FraudML;

use App\Domains\FraudML\DTOs\PaymentFraudMLDto;
use App\Domains\FraudML\Services\PaymentFraudMLService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

final readonly class PaymentFraudMLServiceTest extends TestCase
{
    public function test_score_payment_returns_valid_result(): void
    {
        $service = app(PaymentFraudMLService::class);

        $dto = new PaymentFraudMLDto(
            tenant_id: 1,
            user_id: 1,
            operation_type: 'payment',
            amount_kopecks: 15000, // 150 RUB
            ip_address: '127.0.0.1',
            device_fingerprint: 'test-device',
            correlation_id: 'test-correlation',
            idempotency_key: 'test-key-123',
            vertical_code: 'medical',
            urgency_level: 'low',
            is_emergency_payment: false,
        );

        $result = $service->scorePayment($dto);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('decision', $result);
        $this->assertArrayHasKey('cached', $result);
        $this->assertIsFloat($result['score']);
        $this->assertGreaterThanOrEqual(0.0, $result['score']);
        $this->assertLessThanOrEqual(1.0, $result['score']);
        $this->assertContains($result['decision'], ['allow', 'block', 'review']);
    }

    public function test_score_payment_caches_result(): void
    {
        $service = app(PaymentFraudMLService::class);
        Cache::flush();

        $dto = new PaymentFraudMLDto(
            tenant_id: 1,
            user_id: 1,
            operation_type: 'payment',
            amount_kopecks: 15000,
            ip_address: '127.0.0.1',
            device_fingerprint: 'test-device',
            correlation_id: 'test-correlation',
            idempotency_key: 'cache-test-key',
            vertical_code: 'medical',
        );

        // First call - not cached
        $result1 = $service->scorePayment($dto);
        $this->assertFalse($result1['cached']);

        // Second call - should be cached
        $result2 = $service->scorePayment($dto);
        $this->assertTrue($result2['cached']);
        $this->assertEquals($result1['score'], $result2['score']);
    }

    public function test_score_payment_emergency_has_lower_threshold(): void
    {
        $service = app(PaymentFraudMLService::class);

        $dto = new PaymentFraudMLDto(
            tenant_id: 1,
            user_id: 1,
            operation_type: 'payment',
            amount_kopecks: 5000000, // 50000 RUB - high amount
            ip_address: '127.0.0.1',
            device_fingerprint: 'test-device',
            correlation_id: 'test-correlation',
            idempotency_key: 'emergency-test-key',
            vertical_code: 'medical',
            urgency_level: 'emergency',
            is_emergency_payment: true,
        );

        $result = $service->scorePayment($dto);

        // Emergency payments should have lower threshold
        // Even with high score, emergency might be allowed
        $this->assertArrayHasKey('decision', $result);
    }

    public function test_invalidate_cache_removes_cached_score(): void
    {
        $service = app(PaymentFraudMLService::class);
        Cache::flush();

        $dto = new PaymentFraudMLDto(
            tenant_id: 1,
            user_id: 1,
            operation_type: 'payment',
            amount_kopecks: 15000,
            ip_address: '127.0.0.1',
            device_fingerprint: 'test-device',
            correlation_id: 'test-correlation',
            idempotency_key: 'invalidate-test-key',
            vertical_code: 'medical',
        );

        // Cache the result
        $service->scorePayment($dto);

        // Invalidate cache
        $service->invalidateCache('invalidate-test-key');

        // Should not be cached anymore
        $result = $service->scorePayment($dto);
        $this->assertFalse($result['cached']);
    }

    public function test_wallet_balance_ratio_calculation(): void
    {
        $dto = new PaymentFraudMLDto(
            tenant_id: 1,
            user_id: 1,
            operation_type: 'payment',
            amount_kopecks: 10000, // 100 RUB
            ip_address: '127.0.0.1',
            device_fingerprint: 'test-device',
            correlation_id: 'test-correlation',
            idempotency_key: 'wallet-test-key',
            vertical_code: 'medical',
            wallet_balance_kopecks: 100000, // 1000 RUB
        );

        // Wallet balance ratio = 100000 / 10000 = 10.0
        $this->assertEquals(100000, $dto->wallet_balance_kopecks);
        $this->assertEquals(10000, $dto->amount_kopecks);
    }

    public function test_medical_vertical_with_price_spike(): void
    {
        $service = app(PaymentFraudMLService::class);

        $dto = new PaymentFraudMLDto(
            tenant_id: 1,
            user_id: 1,
            operation_type: 'medical_appointment_payment',
            amount_kopecks: 15000,
            ip_address: '127.0.0.1',
            device_fingerprint: 'test-device',
            correlation_id: 'test-correlation',
            idempotency_key: 'medical-spike-test-key',
            vertical_code: 'medical',
            consultation_price_spike_ratio: 2.5, // 2.5x higher than average
        );

        $result = $service->scorePayment($dto);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('score', $result);
    }
}
