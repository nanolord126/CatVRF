<?php

declare(strict_types=1);

namespace App\Domains\Shared\Payment\Contracts;

interface GatewayInterface
{
    public function createPayment(array $data): array;

    public function getPaymentStatus(string $paymentId): array;

    public function refund(string $paymentId, ?float $amount = null): array;

    public function chargeRecurring(string $customerKey, float $amount, string $orderId, ?string $description = null): array;
}
