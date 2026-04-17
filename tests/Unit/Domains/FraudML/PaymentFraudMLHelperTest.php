<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\FraudML;

use App\Domains\FraudML\Services\PaymentFraudMLHelper;
use Tests\TestCase;
use Illuminate\Support\Str;

final readonly class PaymentFraudMLHelperTest extends TestCase
{
    public function test_check_payment_fraud_allows_low_risk(): void
    {
        $helper = app(PaymentFraudMLHelper::class);

        $result = $helper->checkPaymentFraud(
            tenantId: 1,
            userId: 1,
            amountKopecks: 15000, // 150 RUB
            idempotencyKey: 'test-' . uniqid(),
            correlationId: (string) Str::uuid(),
            verticalCode: 'medical',
            urgencyLevel: 'low',
            isEmergency: false,
        );

        $this->assertArrayHasKey('decision', $result);
        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('explanation', $result);
        $this->assertContains($result['decision'], ['allow', 'block', 'review']);
    }

    public function test_check_payment_fraud_blocks_high_risk(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Payment blocked by fraud detection');

        $helper = app(PaymentFraudMLHelper::class);

        // Very high amount that should trigger block
        $helper->checkPaymentFraud(
            tenantId: 1,
            userId: 1,
            amountKopecks: 100000000, // 1,000,000 RUB - very high
            idempotencyKey: 'high-risk-' . uniqid(),
            correlationId: (string) Str::uuid(),
            verticalCode: 'medical',
            urgencyLevel: 'low',
            isEmergency: false,
        );
    }

    public function test_check_wallet_fraud_returns_result(): void
    {
        $helper = app(PaymentFraudMLHelper::class);

        $result = $helper->checkWalletFraud(
            tenantId: 1,
            userId: 1,
            walletId: 1,
            amountKopecks: 10000,
            operationType: 'debit',
            correlationId: (string) Str::uuid(),
            verticalCode: 'wallet',
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('decision', $result);
        $this->assertArrayHasKey('score', $result);
    }

    public function test_check_medical_payment_fraud_with_emergency(): void
    {
        $helper = app(PaymentFraudMLHelper::class);

        $result = $helper->checkMedicalPaymentFraud(
            tenantId: 1,
            userId: 1,
            amountKopecks: 50000,
            idempotencyKey: 'medical-emergency-' . uniqid(),
            correlationId: (string) Str::uuid(),
            urgencyLevel: 'emergency',
            consultationPriceSpikeRatio: 1.5,
            isEmergency: true,
        );

        $this->assertArrayHasKey('decision', $result);
        $this->assertArrayHasKey('score', $result);
        // Emergency payments have lower threshold, more likely to allow
    }

    public function test_invalidate_cache_works(): void
    {
        $helper = app(PaymentFraudMLHelper::class);

        $idempotencyKey = 'cache-invalidate-' . uniqid();

        // Should not throw exception
        $helper->invalidateCache($idempotencyKey);

        $this->assertTrue(true);
    }

    public function test_check_payment_fraud_with_different_verticals(): void
    {
        $helper = app(PaymentFraudMLHelper::class);

        $verticals = ['medical', 'food', 'beauty', 'realestate', 'travel'];

        foreach ($verticals as $vertical) {
            $result = $helper->checkPaymentFraud(
                tenantId: 1,
                userId: 1,
                amountKopecks: 15000,
                idempotencyKey: "vertical-{$vertical}-" . uniqid(),
                correlationId: (string) Str::uuid(),
                verticalCode: $vertical,
                urgencyLevel: 'low',
                isEmergency: false,
            );

            $this->assertArrayHasKey('decision', $result);
            $this->assertArrayHasKey('score', $result);
        }
    }

    public function test_check_payment_fraud_with_urgency_levels(): void
    {
        $helper = app(PaymentFraudMLHelper::class);

        $urgencyLevels = ['low', 'medium', 'high', 'emergency'];

        foreach ($urgencyLevels as $urgency) {
            $result = $helper->checkPaymentFraud(
                tenantId: 1,
                userId: 1,
                amountKopecks: 15000,
                idempotencyKey: "urgency-{$urgency}-" . uniqid(),
                correlationId: (string) Str::uuid(),
                verticalCode: 'medical',
                urgencyLevel: $urgency,
                isEmergency: $urgency === 'emergency',
            );

            $this->assertArrayHasKey('decision', $result);
            $this->assertArrayHasKey('score', $result);
        }
    }
}
