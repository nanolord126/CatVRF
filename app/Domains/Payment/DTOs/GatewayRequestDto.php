<?php

declare(strict_types=1);

namespace App\Domains\Payment\DTOs;

use App\Domains\Payment\Enums\GatewayProvider;

/**
 * Gateway Request DTO.
 *
 * Immutable data transfer object for payment gateway requests.
 */
final readonly class GatewayRequestDto
{
    public function __construct(
        public GatewayProvider $provider,
        public int $amountKopecks,
        public string $correlationId,
        public string $description,
        public string $returnUrl,
        public int $tenantId,
        public ?string $providerPaymentId = null,
    ) {}

    /**
     * Create from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            provider: GatewayProvider::from($data['provider']),
            amountKopecks: (int) $data['amount_kopecks'],
            correlationId: $data['correlation_id'],
            description: $data['description'],
            returnUrl: $data['return_url'],
            tenantId: (int) $data['tenant_id'],
            providerPaymentId: $data['provider_payment_id'] ?? null,
        );
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider->value,
            'amount_kopecks' => $this->amountKopecks,
            'correlation_id' => $this->correlationId,
            'description' => $this->description,
            'return_url' => $this->returnUrl,
            'tenant_id' => $this->tenantId,
            'provider_payment_id' => $this->providerPaymentId,
        ];
    }
}
