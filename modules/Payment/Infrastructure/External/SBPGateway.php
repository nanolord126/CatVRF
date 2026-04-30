<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\External;

use Modules\Payment\Domain\Contracts\PaymentGatewayInterface;
use Modules\Payment\Domain\ValueObjects\PaymentProvider;

// TODO: Implement SBP gateway with full API integration
final class SBPGateway implements PaymentGatewayInterface
{
    public function initPayment(int $amountKopecks, string $idempotencyKey, string $correlationId, string $description = ''): array
    {
        // TODO: Implement SBP API call
        return ['payment_id' => '', 'redirect_url' => '', 'provider_response' => []];
    }

    public function capture(string $providerPaymentId, int $amountKopecks, string $correlationId): array
    {
        // TODO: Implement SBP capture
        return ['status' => '', 'provider_response' => []];
    }

    public function refund(string $providerPaymentId, int $amountKopecks, string $correlationId): array
    {
        // TODO: Implement SBP refund
        return ['refund_id' => '', 'status' => '', 'provider_response' => []];
    }

    public function handleWebhook(array $payload, string $signature, string $correlationId): array
    {
        // TODO: Implement SBP webhook handling
        return ['payment_id' => '', 'status' => '', 'amount_kopecks' => 0];
    }

    public function getProvider(): PaymentProvider
    {
        return PaymentProvider::SBP;
    }
}
