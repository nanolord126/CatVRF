<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\External;

use Modules\Payment\Domain\Contracts\PaymentGatewayInterface;
use Modules\Payment\Domain\ValueObjects\PaymentProvider;

// TODO: Implement Sber gateway with full API integration
final class SberGateway implements PaymentGatewayInterface
{
    public function initPayment(int $amountKopecks, string $idempotencyKey, string $correlationId, string $description = ''): array
    {
        // TODO: Implement Sber API call
        return ['payment_id' => '', 'redirect_url' => '', 'provider_response' => []];
    }

    public function capture(string $providerPaymentId, int $amountKopecks, string $correlationId): array
    {
        // TODO: Implement Sber capture
        return ['status' => '', 'provider_response' => []];
    }

    public function refund(string $providerPaymentId, int $amountKopecks, string $correlationId): array
    {
        // TODO: Implement Sber refund
        return ['refund_id' => '', 'status' => '', 'provider_response' => []];
    }

    public function handleWebhook(array $payload, string $signature, string $correlationId): array
    {
        // TODO: Implement Sber webhook handling
        return ['payment_id' => '', 'status' => '', 'amount_kopecks' => 0];
    }

    public function getProvider(): PaymentProvider
    {
        return PaymentProvider::SBER;
    }
}
