<?php

namespace Modules\Payments\Gateways;

interface PaymentGatewayInterface
{
    public function createPayment(float $amount, string $orderId, array $data = []): array;
    public function checkStatus(string $paymentId): string;
    public function refund(string $paymentId, float $amount): bool;
}
