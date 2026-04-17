<?php

declare(strict_types=1);

namespace App\Domains\Payment\Services;

use App\Domains\Payment\DTOs\GatewayRequestDto;
use App\Domains\Payment\DTOs\GatewayResponseDto;
use App\Domains\Payment\Enums\GatewayProvider;
use App\Domains\Payment\Enums\PaymentStatus;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Psr\Log\LoggerInterface;

/**
 * PaymentGatewayService - Handles external payment gateway communication.
 *
 * CRITICAL: Includes circuit breaker to prevent cascading failures.
 * Never called inside DB::transaction - always external to database operations.
 *
 * Architecture:
 * - Circuit breaker pattern for resilience
 * - Separate implementations per provider (YooKassa, Tinkoff, etc.)
 * - Timeout handling (5s default)
 * - Retry with exponential backoff (max 3 attempts)
 *
 * @package App\Domains\Payment\Services
 */
final readonly class PaymentGatewayService
{
    private const CIRCUIT_BREAKER_THRESHOLD = 5; // Failures before opening
    private const CIRCUIT_BREAKER_TIMEOUT = 60; // Seconds to stay open
    private const MAX_RETRIES = 3;
    private const DEFAULT_TIMEOUT = 5; // Seconds

    private const CIRCUIT_PREFIX = 'payment:circuit:';

    public function __construct(
        private LoggerInterface $logger,
    ) {}

    /**
     * Create payment at external gateway.
     *
     * @param GatewayRequestDto $dto
     * @return GatewayResponseDto
     * @throws \RuntimeException If gateway unavailable or circuit open
     */
    public function createPayment(GatewayRequestDto $dto): GatewayResponseDto
    {
        $provider = $dto->provider;

        if ($this->isCircuitOpen($provider)) {
            $this->logger->error('Payment gateway circuit breaker open', [
                'provider' => $provider->value,
                'correlation_id' => $dto->correlationId,
            ]);

            throw new \RuntimeException("Payment gateway {$provider->value} is temporarily unavailable due to circuit breaker");
        }

        return $this->executeWithRetry(
            fn () => $this->doCreatePayment($dto),
            $provider,
            $dto->correlationId,
        );
    }

    /**
     * Capture payment at external gateway.
     *
     * @param GatewayRequestDto $dto
     * @return GatewayResponseDto
     */
    public function capturePayment(GatewayRequestDto $dto): GatewayResponseDto
    {
        $provider = $dto->provider;

        if ($this->isCircuitOpen($provider)) {
            throw new \RuntimeException("Payment gateway {$provider->value} is temporarily unavailable");
        }

        return $this->executeWithRetry(
            fn () => $this->doCapturePayment($dto),
            $provider,
            $dto->correlationId,
        );
    }

    /**
     * Refund payment at external gateway.
     *
     * @param GatewayRequestDto $dto
     * @return GatewayResponseDto
     */
    public function refundPayment(GatewayRequestDto $dto): GatewayResponseDto
    {
        $provider = $dto->provider;

        if ($this->isCircuitOpen($provider)) {
            throw new \RuntimeException("Payment gateway {$provider->value} is temporarily unavailable");
        }

        return $this->executeWithRetry(
            fn () => $this->doRefundPayment($dto),
            $provider,
            $dto->correlationId,
        );
    }

    /**
     * Get payment status from external gateway.
     *
     * @param GatewayRequestDto $dto
     * @return GatewayResponseDto
     */
    public function getPaymentStatus(GatewayRequestDto $dto): GatewayResponseDto
    {
        $provider = $dto->provider;

        if ($this->isCircuitOpen($provider)) {
            throw new \RuntimeException("Payment gateway {$provider->value} is temporarily unavailable");
        }

        return $this->executeWithRetry(
            fn () => $this->doGetPaymentStatus($dto),
            $provider,
            $dto->correlationId,
        );
    }

    // ─── Provider implementations ─────────────────────────────────────

    private function doCreatePayment(GatewayRequestDto $dto): GatewayResponseDto
    {
        return match ($dto->provider) {
            GatewayProvider::YOOKASSA => $this->yookassaCreatePayment($dto),
            GatewayProvider::TINKOFF => $this->tinkoffCreatePayment($dto),
            default => throw new \InvalidArgumentException("Unsupported provider: {$dto->provider->value}"),
        };
    }

    private function doCapturePayment(GatewayRequestDto $dto): GatewayResponseDto
    {
        return match ($dto->provider) {
            GatewayProvider::YOOKASSA => $this->yookassaCapturePayment($dto),
            GatewayProvider::TINKOFF => $this->tinkoffCapturePayment($dto),
            default => throw new \InvalidArgumentException("Unsupported provider: {$dto->provider->value}"),
        };
    }

    private function doRefundPayment(GatewayRequestDto $dto): GatewayResponseDto
    {
        return match ($dto->provider) {
            GatewayProvider::YOOKASSA => $this->yookassaRefundPayment($dto),
            GatewayProvider::TINKOFF => $this->tinkoffRefundPayment($dto),
            default => throw new \InvalidArgumentException("Unsupported provider: {$dto->provider->value}"),
        };
    }

    private function doGetPaymentStatus(GatewayRequestDto $dto): GatewayResponseDto
    {
        return match ($dto->provider) {
            GatewayProvider::YOOKASSA => $this->yookassaGetStatus($dto),
            GatewayProvider::TINKOFF => $this->tinkoffGetStatus($dto),
            default => throw new \InvalidArgumentException("Unsupported provider: {$dto->provider->value}"),
        };
    }

    // ─── YooKassa implementation ───────────────────────────────────────

    private function yookassaCreatePayment(GatewayRequestDto $dto): GatewayResponseDto
    {
        $response = $this->yookassaClient()->post('/payments', [
            'amount' => [
                'value' => $dto->amountKopecks / 100,
                'currency' => 'RUB',
            ],
            'confirmation' => [
                'type' => 'redirect',
                'return_url' => $dto->returnUrl,
            ],
            'description' => $dto->description,
            'metadata' => [
                'correlation_id' => $dto->correlationId,
                'tenant_id' => $dto->tenantId,
            ],
            'idempotency_key' => $dto->correlationId,
        ]);

        $data = $response->json();

        $this->logger->info('YooKassa payment created', [
            'correlation_id' => $dto->correlationId,
            'payment_id' => $data['id'] ?? null,
            'status' => $data['status'] ?? null,
        ]);

        return new GatewayResponseDto(
            providerPaymentId: $data['id'] ?? '',
            status: $this->mapYooKassaStatus($data['status'] ?? ''),
            amountKopecks: (int) (($data['amount']['value'] ?? 0) * 100),
            confirmationUrl: $data['confirmation']['confirmation_url'] ?? null,
            rawResponse: $data,
        );
    }

    private function yookassaCapturePayment(GatewayRequestDto $dto): GatewayResponseDto
    {
        $response = $this->yookassaClient()->post("/payments/{$dto->providerPaymentId}/capture", [
            'amount' => [
                'value' => $dto->amountKopecks / 100,
                'currency' => 'RUB',
            ],
        ]);

        $data = $response->json();

        return new GatewayResponseDto(
            providerPaymentId: $dto->providerPaymentId,
            status: $this->mapYooKassaStatus($data['status'] ?? ''),
            amountKopecks: (int) (($data['amount']['value'] ?? 0) * 100),
            confirmationUrl: null,
            rawResponse: $data,
        );
    }

    private function yookassaRefundPayment(GatewayRequestDto $dto): GatewayResponseDto
    {
        $response = $this->yookassaClient()->post('/refunds', [
            'amount' => [
                'value' => $dto->amountKopecks / 100,
                'currency' => 'RUB',
            ],
            'payment_id' => $dto->providerPaymentId,
        ]);

        $data = $response->json();

        return new GatewayResponseDto(
            providerPaymentId: $data['id'] ?? '',
            status: PaymentStatus::REFUNDED,
            amountKopecks: (int) (($data['amount']['value'] ?? 0) * 100),
            confirmationUrl: null,
            rawResponse: $data,
        );
    }

    private function yookassaGetStatus(GatewayRequestDto $dto): GatewayResponseDto
    {
        $response = $this->yookassaClient()->get("/payments/{$dto->providerPaymentId}");
        $data = $response->json();

        return new GatewayResponseDto(
            providerPaymentId: $dto->providerPaymentId,
            status: $this->mapYooKassaStatus($data['status'] ?? ''),
            amountKopecks: (int) (($data['amount']['value'] ?? 0) * 100),
            confirmationUrl: null,
            rawResponse: $data,
        );
    }

    // ─── Tinkoff implementation ─────────────────────────────────────────

    private function tinkoffCreatePayment(GatewayRequestDto $dto): GatewayResponseDto
    {
        $response = $this->tinkoffClient()->post('Init', [
            'Amount' => $dto->amountKopecks,
            'OrderId' => $dto->correlationId,
            'Description' => $dto->description,
            'SuccessURL' => $dto->returnUrl,
            'DATA' => [
                'correlation_id' => $dto->correlationId,
                'tenant_id' => $dto->tenantId,
            ],
        ]);

        $data = $response->json();

        $this->logger->info('Tinkoff payment initialized', [
            'correlation_id' => $dto->correlationId,
            'payment_id' => $data['PaymentId'] ?? null,
            'status' => $data['Status'] ?? null,
        ]);

        return new GatewayResponseDto(
            providerPaymentId: $data['PaymentId'] ?? '',
            status: PaymentStatus::PENDING,
            amountKopecks: $dto->amountKopecks,
            confirmationUrl: $data['PaymentURL'] ?? null,
            rawResponse: $data,
        );
    }

    private function tinkoffCapturePayment(GatewayRequestDto $dto): GatewayResponseDto
    {
        $response = $this->tinkoffClient()->post('Confirm', [
            'PaymentId' => $dto->providerPaymentId,
            'Amount' => $dto->amountKopecks,
        ]);

        $data = $response->json();

        return new GatewayResponseDto(
            providerPaymentId: $dto->providerPaymentId,
            status: $this->mapTinkoffStatus($data['Status'] ?? ''),
            amountKopecks: $dto->amountKopecks,
            confirmationUrl: null,
            rawResponse: $data,
        );
    }

    private function tinkoffRefundPayment(GatewayRequestDto $dto): GatewayResponseDto
    {
        $response = $this->tinkoffClient()->post('Cancel', [
            'PaymentId' => $dto->providerPaymentId,
            'Amount' => $dto->amountKopecks,
        ]);

        $data = $response->json();

        return new GatewayResponseDto(
            providerPaymentId: $dto->providerPaymentId,
            status: PaymentStatus::REFUNDED,
            amountKopecks: $dto->amountKopecks,
            confirmationUrl: null,
            rawResponse: $data,
        );
    }

    private function tinkoffGetStatus(GatewayRequestDto $dto): GatewayResponseDto
    {
        $response = $this->tinkoffClient()->post('GetState', [
            'PaymentId' => $dto->providerPaymentId,
        ]);

        $data = $response->json();

        return new GatewayResponseDto(
            providerPaymentId: $dto->providerPaymentId,
            status: $this->mapTinkoffStatus($data['Status'] ?? ''),
            amountKopecks: $data['Amount'] ?? 0,
            confirmationUrl: null,
            rawResponse: $data,
        );
    }

    // ─── Circuit breaker ───────────────────────────────────────────────

    private function isCircuitOpen(GatewayProvider $provider): bool
    {
        $key = $this->circuitKey($provider);
        $failures = cache($key, 0);
        $openedAt = cache($key . ':opened_at');

        // Check if circuit should be reset
        if ($openedAt && now()->diffInSeconds($openedAt) > self::CIRCUIT_BREAKER_TIMEOUT) {
            cache()->forget($key);
            cache()->forget($key . ':opened_at');
            return false;
        }

        return $failures >= self::CIRCUIT_BREAKER_THRESHOLD;
    }

    private function recordFailure(GatewayProvider $provider): void
    {
        $key = $this->circuitKey($provider);
        $failures = cache($key, 0) + 1;
        cache([$key => $failures], now()->addHours(1));

        if ($failures >= self::CIRCUIT_BREAKER_THRESHOLD) {
            cache([$key . ':opened_at' => now()], now()->addHours(1));
            $this->logger->error('Payment gateway circuit breaker opened', [
                'provider' => $provider->value,
                'failures' => $failures,
            ]);
        }
    }

    private function recordSuccess(GatewayProvider $provider): void
    {
        $key = $this->circuitKey($provider);
        cache()->forget($key);
        cache()->forget($key . ':opened_at');
    }

    private function executeWithRetry(callable $operation, GatewayProvider $provider, string $correlationId): GatewayResponseDto
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                $result = $operation();
                $this->recordSuccess($provider);
                return $result;
            } catch (\Exception $e) {
                $lastException = $e;
                $this->logger->warning('Payment gateway attempt failed', [
                    'provider' => $provider->value,
                    'attempt' => $attempt,
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < self::MAX_RETRIES) {
                    usleep(100000 * $attempt); // Exponential backoff: 100ms, 200ms, 400ms
                }
            }
        }

        $this->recordFailure($provider);
        throw new \RuntimeException(
            "Payment gateway failed after {$attempt} attempts: {$lastException->getMessage()}",
            0,
            $lastException
        );
    }

    // ─── Helpers ───────────────────────────────────────────────────────

    private function yookassaClient(): PendingRequest
    {
        return Http::timeout(self::DEFAULT_TIMEOUT)
            ->withHeaders([
                'Idempotence-Key' => uniqid(),
                'Content-Type' => 'application/json',
            ])
            ->withToken(config('services.yookassa.api_key'))
            ->baseUrl('https://api.yookassa.ru/v3');
    }

    private function tinkoffClient(): PendingRequest
    {
        return Http::timeout(self::DEFAULT_TIMEOUT)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post([
                'TerminalKey' => config('services.tinkoff.terminal_key'),
                'Password' => config('services.tinkoff.password'),
            ])
            ->baseUrl('https://securepay.tinkoff.ru/v2');
    }

    private function mapYooKassaStatus(string $status): PaymentStatus
    {
        return match ($status) {
            'pending' => PaymentStatus::PENDING,
            'waiting_for_capture' => PaymentStatus::WAITING_FOR_CAPTURE,
            'succeeded' => PaymentStatus::COMPLETED,
            'canceled' => PaymentStatus::CANCELLED,
            default => PaymentStatus::PENDING,
        };
    }

    private function mapTinkoffStatus(string $status): PaymentStatus
    {
        return match ($status) {
            'NEW', 'FORM_SHOWED' => PaymentStatus::PENDING,
            'CONFIRMED' => PaymentStatus::COMPLETED,
            'CANCELED', 'REJECTED' => PaymentStatus::CANCELLED,
            'REFUNDED' => PaymentStatus::REFUNDED,
            default => PaymentStatus::PENDING,
        };
    }

    private function circuitKey(GatewayProvider $provider): string
    {
        return self::CIRCUIT_PREFIX . $provider->value;
    }
}
