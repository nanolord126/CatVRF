<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Contracts;

use Modules\Payment\Domain\ValueObjects\PaymentProvider;

/**
 * Interface for payment gateways.
 *
 * Each provider (Tinkoff, Sber, Tochka, etc.) must implement this contract.
 * PaymentCoordinatorService selects the appropriate driver via DI / Factory.
 */
interface PaymentGatewayInterface
{
    /**
     * Initiate payment (create session with provider).
     *
     * @param int $amountKopecks amount in kopecks
     * @param string $idempotencyKey idempotency key
     * @param string $correlationId correlation_id for audit
     * @param string $description payment description
     * @return array{payment_id: string, redirect_url: string, provider_response: array<string, mixed>}
     */
    public function initPayment(
        int $amountKopecks,
        string $idempotencyKey,
        string $correlationId,
        string $description = '',
    ): array;

    /**
     * Capture (confirm) previously authorized payment.
     *
     * @param string $providerPaymentId provider identifier
     * @param int $amountKopecks amount to confirm
     * @param string $correlationId correlation_id for audit
     * @return array{status: string, provider_response: array<string, mixed>}
     */
    public function capture(
        string $providerPaymentId,
        int $amountKopecks,
        string $correlationId,
    ): array;

    /**
     * Execute refund (full or partial).
     *
     * @param string $providerPaymentId provider identifier
     * @param int $amountKopecks refund amount
     * @param string $correlationId correlation_id for audit
     * @return array{refund_id: string, status: string, provider_response: array<string, mixed>}
     */
    public function refund(
        string $providerPaymentId,
        int $amountKopecks,
        string $correlationId,
    ): array;

    /**
     * Handle webhook from provider.
     *
     * @param array<string, mixed> $payload request body
     * @param string $signature signature from provider
     * @param string $correlationId correlation_id
     * @return array{payment_id: string, status: string, amount_kopecks: int}
     */
    public function handleWebhook(
        array $payload,
        string $signature,
        string $correlationId,
    ): array;

    /**
     * Provider implementing this gateway.
     */
    public function getProvider(): PaymentProvider;
}
