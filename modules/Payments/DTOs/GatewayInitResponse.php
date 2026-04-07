<?php declare(strict_types=1);

namespace Modules\Payments\DTOs;

final readonly class GatewayInitResponse
{
    public function __construct(
        public string $providerPaymentId,
        public string $paymentUrl,
        public bool $requires3ds,
    ) {}
}
