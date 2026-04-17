<?php

declare(strict_types=1);

namespace App\Domains\Payment\DTOs;

use App\Domains\Payment\Enums\PaymentStatus;

/**
 * Gateway Response DTO.
 *
 * Immutable data transfer object for payment gateway responses.
 */
final readonly class GatewayResponseDto
{
    public function __construct(
        public string $providerPaymentId,
        public PaymentStatus $status,
        public int $amountKopecks,
        public ?string $confirmationUrl,
        public array $rawResponse,
    ) {}

    /**
     * Create from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            providerPaymentId: $data['provider_payment_id'],
            status: PaymentStatus::from($data['status']),
            amountKopecks: (int) $data['amount_kopecks'],
            confirmationUrl: $data['confirmation_url'] ?? null,
            rawResponse: $data['raw_response'] ?? [],
        );
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'provider_payment_id' => $this->providerPaymentId,
            'status' => $this->status->value,
            'amount_kopecks' => $this->amountKopecks,
            'confirmation_url' => $this->confirmationUrl,
            'raw_response' => $this->rawResponse,
        ];
    }
}
