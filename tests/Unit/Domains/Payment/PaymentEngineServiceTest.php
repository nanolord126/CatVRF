<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment;

use App\Domains\Payment\DTOs\CreatePaymentRecordDto;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Jobs\PaymentFraudCheckJob;
use App\Domains\Payment\Models\PaymentRecord;
use App\Domains\Payment\Services\PaymentEngineService;
use App\Domains\Payment\Services\PaymentService;
use App\Domains\Payment\Services\IdempotencyService;
use App\Domains\Payment\Services\PaymentGatewayService;
use App\Domains\Wallet\Services\AtomicWalletService;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * PaymentEngineService Unit Tests.
 *
 * Tests the new payment flow orchestration.
 */
final class PaymentEngineServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentEngineService $paymentEngine;
    private IdempotencyService $idempotencyService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->idempotencyService = app(IdempotencyService::class);

        $this->paymentEngine = new PaymentEngineService(
            app(\Illuminate\Database\DatabaseManager::class),
            app(\Psr\Log\LoggerInterface::class),
            app(\Illuminate\Contracts\Auth\Guard::class),
            app(FraudControlService::class),
            app(AuditService::class),
            app(PaymentService::class),
            $this->idempotencyService,
            app(PaymentGatewayService::class),
            app(AtomicWalletService::class),
        );

        // Clear Redis
        \Illuminate\Support\Facades\Redis::flushdb();

        // Fake queue for job dispatch
        Queue::fake();
        Bus::fake();
    }

    protected function tearDown(): void
    {
        \Illuminate\Support\Facades\Redis::flushdb();
        parent::tearDown();
    }

    public function test_create_payment_with_new_correlation_id(): void
    {
        Http::fake([
            'api.yookassa.ru/v3/payments' => Http::response([
                'id' => 'gateway_payment_id',
                'status' => 'pending',
                'amount' => ['value' => '100.00', 'currency' => 'RUB'],
                'confirmation' => ['confirmation_url' => 'https://example.com/confirm'],
            ], 200),
        ]);

        $dto = new CreatePaymentRecordDto(
            userId: 1,
            walletId: null,
            tenantId: 1,
            amountKopecks: 10000,
            providerCode: 'yookassa',
            correlationId: 'test_' . uniqid(),
            description: 'Test payment',
            verticalCode: 'medical',
        );

        $payment = $this->paymentEngine->createPayment($dto, 'https://example.com/return');

        $this->assertInstanceOf(PaymentRecord::class, $payment);
        $this->assertSame(PaymentStatus::PENDING, $payment->status);
        $this->assertSame('gateway_payment_id', $payment->provider_payment_id);

        // Assert fraud check job was dispatched
        Bus::assertDispatched(PaymentFraudCheckJob::class);
    }

    public function test_create_payment_returns_existing_for_duplicate_correlation_id(): void
    {
        // Create first payment
        Http::fake([
            'api.yookassa.ru/v3/payments' => Http::response([
                'id' => 'gateway_payment_id_1',
                'status' => 'pending',
                'amount' => ['value' => '100.00', 'currency' => 'RUB'],
                'confirmation' => ['confirmation_url' => 'https://example.com/confirm'],
            ], 200),
        ]);

        $correlationId = 'duplicate_test_' . uniqid();

        $dto1 = new CreatePaymentRecordDto(
            userId: 1,
            walletId: null,
            tenantId: 1,
            amountKopecks: 10000,
            providerCode: 'yookassa',
            correlationId: $correlationId,
            description: 'Test payment 1',
            verticalCode: 'medical',
        );

        $payment1 = $this->paymentEngine->createPayment($dto1, 'https://example.com/return');

        // Try to create payment with same correlation ID
        $dto2 = new CreatePaymentRecordDto(
            userId: 1,
            walletId: null,
            tenantId: 1,
            amountKopecks: 20000, // Different amount
            providerCode: 'yookassa',
            correlationId: $correlationId, // Same correlation ID
            description: 'Test payment 2',
            verticalCode: 'medical',
        );

        $payment2 = $this->paymentEngine->createPayment($dto2, 'https://example.com/return');

        $this->assertSame($payment1->id, $payment2->id, 'Should return existing payment');
        $this->assertSame(10000, $payment2->amount_kopecks, 'Should preserve original amount');
    }

    public function test_create_payment_with_wallet_hold(): void
    {
        Http::fake([
            'api.yookassa.ru/v3/payments' => Http::response([
                'id' => 'gateway_payment_id',
                'status' => 'pending',
                'amount' => ['value' => '100.00', 'currency' => 'RUB'],
                'confirmation' => ['confirmation_url' => 'https://example.com/confirm'],
            ], 200),
        ]);

        // Create wallet
        $wallet = \App\Domains\Wallet\Models\Wallet::factory()->create([
            'current_balance' => 50000, // 500 RUB
            'hold_amount' => 0,
        ]);

        $dto = new CreatePaymentRecordDto(
            userId: 1,
            walletId: $wallet->id,
            tenantId: 1,
            amountKopecks: 10000, // 100 RUB
            providerCode: 'yookassa',
            correlationId: 'test_' . uniqid(),
            description: 'Test payment with hold',
            verticalCode: 'medical',
        );

        $payment = $this->paymentEngine->createPayment($dto, 'https://example.com/return');

        $this->assertInstanceOf(PaymentRecord::class, $payment);

        // Refresh wallet to check hold
        $wallet->refresh();
        $this->assertSame(40000, $wallet->current_balance, 'Balance should be reduced by hold amount');
        $this->assertSame(10000, $wallet->hold_amount, 'Hold amount should be set');
    }

    public function test_gateway_failure_marks_payment_as_failed(): void
    {
        Http::fake([
            'api.yookassa.ru/v3/payments' => Http::response(null, 500),
        ]);

        $dto = new CreatePaymentRecordDto(
            userId: 1,
            walletId: null,
            tenantId: 1,
            amountKopecks: 10000,
            providerCode: 'yookassa',
            correlationId: 'test_' . uniqid(),
            description: 'Test payment with failure',
            verticalCode: 'medical',
        );

        $this->expectException(\RuntimeException::class);

        $this->paymentEngine->createPayment($dto, 'https://example.com/return');

        // Payment should be marked as failed
        $payment = PaymentRecord::where('correlation_id', $dto->correlationId)->first();
        $this->assertNotNull($payment);
        $this->assertSame(PaymentStatus::FAILED, $payment->status);
    }

    public function test_capture_payment(): void
    {
        Http::fake([
            'api.yookassa.ru/v3/payments/gateway_payment_id/capture' => Http::response([
                'id' => 'gateway_payment_id',
                'status' => 'succeeded',
                'amount' => ['value' => '100.00', 'currency' => 'RUB'],
            ], 200),
        ]);

        $payment = PaymentRecord::factory()->create([
            'status' => PaymentStatus::WAITING_FOR_CAPTURE,
            'provider_payment_id' => 'gateway_payment_id',
            'amount_kopecks' => 10000,
            'provider_code' => 'yookassa',
        ]);

        $result = $this->paymentEngine->capturePayment($payment->id, 'test_' . uniqid());

        $this->assertSame(PaymentStatus::COMPLETED, $result->status);
    }

    public function test_capture_payment_fails_for_invalid_status(): void
    {
        $payment = PaymentRecord::factory()->create([
            'status' => PaymentStatus::PENDING, // Wrong status
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be captured from status');

        $this->paymentEngine->capturePayment($payment->id, 'test_' . uniqid());
    }

    public function test_refund_payment_full(): void
    {
        Http::fake([
            'api.yookassa.ru/v3/refunds' => Http::response([
                'id' => 'refund_id',
                'status' => 'succeeded',
                'amount' => ['value' => '100.00', 'currency' => 'RUB'],
            ], 200),
        ]);

        $payment = PaymentRecord::factory()->create([
            'status' => PaymentStatus::COMPLETED,
            'provider_payment_id' => 'gateway_payment_id',
            'amount_kopecks' => 10000,
            'provider_code' => 'yookassa',
        ]);

        $result = $this->paymentEngine->refundPayment($payment->id, null, 'test_' . uniqid());

        $this->assertSame(PaymentStatus::REFUNDED, $result->status);
    }

    public function test_refund_payment_partial(): void
    {
        Http::fake([
            'api.yookassa.ru/v3/refunds' => Http::response([
                'id' => 'refund_id',
                'status' => 'succeeded',
                'amount' => ['value' => '50.00', 'currency' => 'RUB'],
            ], 200),
        ]);

        $payment = PaymentRecord::factory()->create([
            'status' => PaymentStatus::COMPLETED,
            'provider_payment_id' => 'gateway_payment_id',
            'amount_kopecks' => 10000,
            'provider_code' => 'yookassa',
        ]);

        $result = $this->paymentEngine->refundPayment($payment->id, 5000, 'test_' . uniqid());

        $this->assertSame(PaymentStatus::PARTIALLY_REFUNDED, $result->status);
    }
}
