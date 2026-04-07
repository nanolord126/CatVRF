<?php declare(strict_types=1);

namespace Modules\Payments\Gateways;

use Modules\Payments\DTOs\GatewayInitResponse;

interface PaymentGatewayInterface
{
    public function init(array $payload): GatewayInitResponse;

    public function getStatus(string $providerPaymentId): string;

    public function refund(string $providerPaymentId, int $amount): bool;

    public function validateWebhook(array $payload): bool;

    public function parseWebhook(array $payload): array;
}
