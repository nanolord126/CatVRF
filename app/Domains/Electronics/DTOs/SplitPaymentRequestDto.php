<?php declare(strict_types=1);

namespace App\Domains\Electronics\DTOs;

final readonly class SplitPaymentRequestDto
{
    /**
     * @param array<array{source: string, amount_kopecks: int, metadata: array<string, mixed>}> $paymentSources
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public int $orderId,
        public int $userId,
        public string $correlationId,
        public int $totalAmountKopecks,
        public array $paymentSources,
        public bool $useEscrow,
        public int $escrowReleaseDays,
        public array $metadata,
        public ?string $idempotencyKey = null,
    ) {
    }

    public static function fromRequest(array $data, int $userId, string $correlationId): self
    {
        return new self(
            orderId: (int) $data['order_id'],
            userId: $userId,
            correlationId: $correlationId,
            totalAmountKopecks: (int) $data['total_amount_kopecks'],
            paymentSources: (array) ($data['payment_sources'] ?? []),
            useEscrow: (bool) ($data['use_escrow'] ?? false),
            escrowReleaseDays: (int) ($data['escrow_release_days'] ?? 7),
            metadata: (array) ($data['metadata'] ?? []),
            idempotencyKey: $data['idempotency_key'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'user_id' => $this->userId,
            'correlation_id' => $this->correlationId,
            'total_amount_kopecks' => $this->totalAmountKopecks,
            'payment_sources' => $this->paymentSources,
            'use_escrow' => $this->useEscrow,
            'escrow_release_days' => $this->escrowReleaseDays,
            'metadata' => $this->metadata,
            'idempotency_key' => $this->idempotencyKey,
        ];
    }

    public function validatePaymentSources(): bool
    {
        $total = array_sum(array_column($this->paymentSources, 'amount_kopecks'));
        return $total === $this->totalAmountKopecks;
    }
}
