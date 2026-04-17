<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment;

use App\Domains\Payment\DTOs\GatewayRequestDto;
use App\Domains\Payment\DTOs\GatewayResponseDto;
use App\Domains\Payment\Enums\GatewayProvider;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Services\PaymentGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * PaymentGatewayService Unit Tests.
 *
 * Tests gateway communication, circuit breaker, and retry logic.
 */
final class PaymentGatewayServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentGatewayService $gatewayService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gatewayService = new PaymentGatewayService(
            $this->app->make(\Psr\Log\LoggerInterface::class),
        );

        // Clear circuit breaker state
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    public function test_create_payment_returns_response(): void
    {
        Http::fake([
            'api.yookassa.ru/v3/payments' => Http::response([
                'id' => 'test_payment_id',
                'status' => 'pending',
                'amount' => ['value' => '100.00', 'currency' => 'RUB'],
                'confirmation' => ['confirmation_url' => 'https://example.com/confirm'],
            ], 200),
        ]);

        $dto = new GatewayRequestDto(
            provider: GatewayProvider::YOOKASSA,
            amountKopecks: 10000,
            correlationId: 'test_' . uniqid(),
            description: 'Test payment',
            returnUrl: 'https://example.com/return',
            tenantId: 1,
        );

        $response = $this->gatewayService->createPayment($dto);

        $this->assertInstanceOf(GatewayResponseDto::class, $response);
        $this->assertSame('test_payment_id', $response->providerPaymentId);
        $this->assertSame(PaymentStatus::PENDING, $response->status);
        $this->assertSame(10000, $response->amountKopecks);
        $this->assertSame('https://example.com/confirm', $response->confirmationUrl);
    }

    public function test_circuit_breaker_opens_after_failures(): void
    {
        // Configure to open after 1 failure for testing
        $reflection = new \ReflectionClass($this->gatewayService);
        $thresholdProperty = $reflection->getProperty('CIRCUIT_BREAKER_THRESHOLD');
        $thresholdProperty->setAccessible(true);
        $thresholdProperty->setValue($this->gatewayService, 1);

        Http::fake([
            'api.yookassa.ru/v3/payments' => Http::response(null, 500),
        ]);

        $dto = new GatewayRequestDto(
            provider: GatewayProvider::YOOKASSA,
            amountKopecks: 10000,
            correlationId: 'test_' . uniqid(),
            description: 'Test payment',
            returnUrl: 'https://example.com/return',
            tenantId: 1,
        );

        // First call should fail and open circuit
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Payment gateway failed after');
        $this->gatewayService->createPayment($dto);

        // Second call should fail immediately due to circuit breaker
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('temporarily unavailable due to circuit breaker');
        $this->gatewayService->createPayment($dto);
    }

    public function test_circuit_breaker_resets_after_timeout(): void
    {
        // Configure fast timeout for testing
        $reflection = new \ReflectionClass($this->gatewayService);
        $thresholdProperty = $reflection->getProperty('CIRCUIT_BREAKER_THRESHOLD');
        $thresholdProperty->setAccessible(true);
        $thresholdProperty->setValue($this->gatewayService, 1);

        $timeoutProperty = $reflection->getProperty('CIRCUIT_BREAKER_TIMEOUT');
        $timeoutProperty->setAccessible(true);
        $timeoutProperty->setValue($this->gatewayService, 1); // 1 second

        Http::fake([
            'api.yookassa.ru/v3/payments' => Http::sequence()
                ->push(null, 500) // First call fails
                ->push([
                    'id' => 'test_payment_id',
                    'status' => 'pending',
                    'amount' => ['value' => '100.00', 'currency' => 'RUB'],
                ], 200), // Second call succeeds
        ]);

        $dto = new GatewayRequestDto(
            provider: GatewayProvider::YOOKASSA,
            amountKopecks: 10000,
            correlationId: 'test_' . uniqid(),
            description: 'Test payment',
            returnUrl: 'https://example.com/return',
            tenantId: 1,
        );

        // First call fails
        try {
            $this->gatewayService->createPayment($dto);
        } catch (\RuntimeException $e) {
            // Expected
        }

        // Wait for circuit breaker timeout
        sleep(2);

        // Circuit should be closed now
        Http::fake([
            'api.yookassa.ru/v3/payments' => Http::response([
                'id' => 'test_payment_id',
                'status' => 'pending',
                'amount' => ['value' => '100.00', 'currency' => 'RUB'],
            ], 200),
        ]);

        $response = $this->gatewayService->createPayment($dto);
        $this->assertInstanceOf(GatewayResponseDto::class, $response);
    }

    public function test_retry_with_exponential_backoff(): void
    {
        $attemptCount = 0;

        Http::fake(function () use (&$attemptCount) {
            $attemptCount++;
            if ($attemptCount < 3) {
                return Http::response(null, 500);
            }
            return Http::response([
                'id' => 'test_payment_id',
                'status' => 'pending',
                'amount' => ['value' => '100.00', 'currency' => 'RUB'],
            ], 200);
        });

        $dto = new GatewayRequestDto(
            provider: GatewayProvider::YOOKASSA,
            amountKopecks: 10000,
            correlationId: 'test_' . uniqid(),
            description: 'Test payment',
            returnUrl: 'https://example.com/return',
            tenantId: 1,
        );

        $response = $this->gatewayService->createPayment($dto);

        $this->assertInstanceOf(GatewayResponseDto::class, $response);
        $this->assertSame(3, $attemptCount, 'Should retry 3 times');
    }

    public function test_capture_payment(): void
    {
        Http::fake([
            'api.yookassa.ru/v3/payments/test_payment_id/capture' => Http::response([
                'id' => 'test_payment_id',
                'status' => 'succeeded',
                'amount' => ['value' => '100.00', 'currency' => 'RUB'],
            ], 200),
        ]);

        $dto = new GatewayRequestDto(
            provider: GatewayProvider::YOOKASSA,
            amountKopecks: 10000,
            correlationId: 'test_' . uniqid(),
            description: 'Capture payment',
            returnUrl: '',
            tenantId: 1,
            providerPaymentId: 'test_payment_id',
        );

        $response = $this->gatewayService->capturePayment($dto);

        $this->assertSame(PaymentStatus::COMPLETED, $response->status);
    }

    public function test_refund_payment(): void
    {
        Http::fake([
            'api.yookassa.ru/v3/refunds' => Http::response([
                'id' => 'refund_id',
                'status' => 'succeeded',
                'amount' => ['value' => '100.00', 'currency' => 'RUB'],
            ], 200),
        ]);

        $dto = new GatewayRequestDto(
            provider: GatewayProvider::YOOKASSA,
            amountKopecks: 10000,
            correlationId: 'test_' . uniqid(),
            description: 'Refund payment',
            returnUrl: '',
            tenantId: 1,
            providerPaymentId: 'test_payment_id',
        );

        $response = $this->gatewayService->refundPayment($dto);

        $this->assertSame(PaymentStatus::REFUNDED, $response->status);
    }

    public function test_get_payment_status(): void
    {
        Http::fake([
            'api.yookassa.ru/v3/payments/test_payment_id' => Http::response([
                'id' => 'test_payment_id',
                'status' => 'succeeded',
                'amount' => ['value' => '100.00', 'currency' => 'RUB'],
            ], 200),
        ]);

        $dto = new GatewayRequestDto(
            provider: GatewayProvider::YOOKASSA,
            amountKopecks: 10000,
            correlationId: 'test_' . uniqid(),
            description: 'Get status',
            returnUrl: '',
            tenantId: 1,
            providerPaymentId: 'test_payment_id',
        );

        $response = $this->gatewayService->getPaymentStatus($dto);

        $this->assertSame(PaymentStatus::COMPLETED, $response->status);
    }

    public function test_unsupported_provider_throws_exception(): void
    {
        $dto = new GatewayRequestDto(
            provider: GatewayProvider::STRIPE, // Not implemented
            amountKopecks: 10000,
            correlationId: 'test_' . uniqid(),
            description: 'Test payment',
            returnUrl: 'https://example.com/return',
            tenantId: 1,
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported provider');

        $this->gatewayService->createPayment($dto);
    }
}
